"""
Plant Disease Detection — Python Flask API

Run:
    python api.py

API:
    http://localhost:5000
"""

import os
import io
import pickle
import numpy as np

from flask import Flask, request, jsonify, make_response
from PIL import Image

# =========================================================
# PATHS
# =========================================================

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

MODEL_PATH = os.path.join(BASE_DIR, "model.pkl")

# =========================================================
# FLASK APP
# =========================================================

app = Flask(__name__)

# =========================================================
# CORS
# =========================================================

def cors_response(data, status=200):

    resp = make_response(jsonify(data), status)

    resp.headers["Access-Control-Allow-Origin"] = "*"
    resp.headers["Access-Control-Allow-Headers"] = "Content-Type"
    resp.headers["Access-Control-Allow-Methods"] = "GET, POST, OPTIONS"

    return resp


@app.after_request
def add_cors(response):

    response.headers["Access-Control-Allow-Origin"] = "*"
    response.headers["Access-Control-Allow-Headers"] = "Content-Type"
    response.headers["Access-Control-Allow-Methods"] = "GET, POST, OPTIONS"

    return response


# =========================================================
# FEATURE EXTRACTION
# MUST MATCH train_model.py EXACTLY
# =========================================================

def extract_features(image):

    img = image.convert("RGB").resize((64, 64))

    arr = np.array(img, dtype=np.float32)

    feats = []

    # -----------------------------------------------------
    # Color statistics
    # -----------------------------------------------------

    for c in range(3):

        ch = arr[:, :, c]

        feats += [
            ch.mean(),
            ch.std(),
            ch.min(),
            ch.max(),
            np.percentile(ch, 25),
            np.percentile(ch, 75),
        ]

    # -----------------------------------------------------
    # RGB ratios
    # -----------------------------------------------------

    r = arr[:, :, 0]
    g = arr[:, :, 1]
    b = arr[:, :, 2]

    eps = 1e-6

    feats += [
        (g / (r + eps)).mean(),
        (r / (g + eps)).mean(),
        (b / (g + eps)).mean(),
    ]

    # -----------------------------------------------------
    # Histograms
    # -----------------------------------------------------

    for c in range(3):

        h, _ = np.histogram(
            arr[:, :, c],
            bins=16,
            range=(0, 256)
        )

        feats += (h / h.sum()).tolist()

    # -----------------------------------------------------
    # Texture features
    # -----------------------------------------------------

    gray = arr.mean(axis=2)

    lap = (
        -gray[:-2, 1:-1]
        -gray[2:, 1:-1]
        -gray[1:-1, :-2]
        -gray[1:-1, 2:]
        +4 * gray[1:-1, 1:-1]
    )

    feats += [
        lap.var(),
        lap.mean()
    ]

    return np.array(feats, dtype=np.float32)


# =========================================================
# LOAD MODEL
# =========================================================

print("Loading model...")

with open(MODEL_PATH, "rb") as f:

    model_data = pickle.load(f)

pipeline = model_data["pipeline"]

CLASSES = model_data["classes"]

print("Model loaded successfully.")
print("Classes:", CLASSES)

# =========================================================
# DISEASE DATABASE
# =========================================================

DISEASE_DB = {

    "healthy": {
        "label": "Healthy Plant",
        "emoji": "🌿",
        "severity": "None",
        "color": "#16a34a",

        "description":
        "Your plant appears healthy with normal green coloration and no visible disease symptoms.",

        "precautions": [
            "Water regularly but avoid overwatering",
            "Ensure 6–8 hours of sunlight daily",
            "Apply balanced fertilizer every 2–3 weeks",
            "Inspect leaves weekly for pests or infections",
            "Maintain proper airflow around plants",
            "Remove dead or yellow leaves promptly",
            "Keep soil nutrient-rich and well-drained",
        ],
    },

    "bacterial_blight": {
        "label": "Bacterial Blight",
        "emoji": "🟡",
        "severity": "Normal",
        "color": "#3b8102",

        "description":
        "Bacterial Blight detected. Yellow-brown lesions and water-soaked patches are visible on leaves.",

        "precautions": [
            "Remove infected leaves immediately",
            "Avoid overhead watering",
            "Use copper-based bactericide sprays",
            "Disinfect gardening tools regularly",
            "Improve drainage and airflow",
            "Do not compost infected material",
            "Use crop rotation next season",
        ],
    },

    "early_blight": {
        "label": "Early Blight",
        "emoji": "🟤",
        "severity": "Medium",
        "color": "#abc409",

        "description":
        "Early Blight detected. Dark brown lesions with concentric rings are visible on leaves.",

        "precautions": [
            "Remove lower infected leaves",
            "Apply Mancozeb or Chlorothalonil fungicide",
            "Mulch soil to prevent fungal spread",
            "Avoid wetting foliage while watering",
            "Rotate crops every season",
            "Destroy infected plant debris",
            "Maintain proper plant spacing",
        ],
    },

    "leaf_rust": {
        "label": "Leaf Rust",
        "emoji": "🟠",
        "severity": "Very–High",
        "color": "#ea580c",

        "description":
        "Leaf Rust detected. Orange rust-like pustules are present on leaf surfaces.",

        "precautions": [
            "Apply Propiconazole fungicide",
            "Remove heavily infected leaves",
            "Avoid excessive leaf moisture",
            "Spray neem oil as organic control",
            "Monitor nearby plants for infection",
            "Improve air circulation",
            "Use rust-resistant varieties next season",
        ],
    },

    "powdery_mildew": {
        "label": "Powdery Mildew",
        "emoji": "⚪",
        "severity": "High",
        "color": "#7c3aed",

        "description":
        "Powdery Mildew detected. White powdery fungal coating is visible on leaf surfaces.",

        "precautions": [
            "Spray baking soda solution weekly",
            "Apply sulfur-based fungicide",
            "Prune overcrowded leaves",
            "Avoid excess nitrogen fertilizer",
            "Increase sunlight exposure",
            "Water only at soil level",
            "Remove severely infected leaves",
        ],
    },
}

# =========================================================
# HEALTH ROUTE
# =========================================================

@app.route("/health", methods=["GET"])
def health():

    return jsonify({
        "status": "ok",
        "model": "SVM Plant Disease Classifier"
    })


# =========================================================
# PREDICT ROUTE
# =========================================================

@app.route("/predict", methods=["POST"])
def predict():

    # -----------------------------------------------------
    # Validate upload
    # -----------------------------------------------------

    if "image" not in request.files:

        return cors_response({
            "error": "No image uploaded"
        }, 400)

    file = request.files["image"]

    if file.filename == "":

        return cors_response({
            "error": "No file selected"
        }, 400)

    # -----------------------------------------------------
    # Validate mime type
    # -----------------------------------------------------

    allowed_mime = {
        "image/jpeg",
        "image/jpg",
        "image/png",
        "image/webp",
        "image/bmp",
        "image/gif"
    }

    content_type = (
        file.content_type
        .lower()
        .split(";")[0]
        .strip()
    )

    if content_type not in allowed_mime:

        return cors_response({
            "error": f"Invalid image type: {content_type}"
        }, 415)

    # -----------------------------------------------------
    # Prediction
    # -----------------------------------------------------

    try:

        image_bytes = file.read()

        image = Image.open(
            io.BytesIO(image_bytes)
        ).convert("RGB")

        # extract features
        feats = extract_features(image).reshape(1, -1)

        # probabilities
        probs = pipeline.predict_proba(feats)[0]

        # IMPORTANT FIX:
        # use predict() instead of manual class indexing
        pred_class = pipeline.predict(feats)[0]

        pred_idx = list(CLASSES).index(pred_class)

        confidence = round(
            float(probs[pred_idx]) * 100,
            1
        )

        # disease info
        info = DISEASE_DB[pred_class]

        # all class probabilities
        all_probs = {}

        for i, cls in enumerate(CLASSES):

            all_probs[cls] = round(
                float(probs[i]) * 100,
                1
            )

        # -------------------------------------------------
        # Response
        # -------------------------------------------------

        return cors_response({

            "success": True,

            "disease": pred_class,

            "label": info["label"],

            "emoji": info["emoji"],

            "severity": info["severity"],

            "color": info["color"],

            "description": info["description"],

            "confidence": confidence,

            "precautions": info["precautions"],

            "all_probabilities": all_probs

        })

    except Exception as e:

        return cors_response({
            "error": f"Prediction failed: {str(e)}"
        }, 500)


# =========================================================
# MAIN
# =========================================================

if __name__ == "__main__":

    print("=" * 55)
    print("🌱 Plant Disease Detection API")
    print("Running on http://localhost:5000")
    print("=" * 55)

    app.run(
        host="0.0.0.0",
        port=5000,
        debug=False
    )