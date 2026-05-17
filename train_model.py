"""
Train SVM plant disease classifier.

Run:
    python train_model.py
"""

import os
import pickle
import numpy as np

from PIL import Image

from sklearn.svm import SVC
from sklearn.preprocessing import StandardScaler
from sklearn.pipeline import Pipeline
from sklearn.model_selection import cross_val_score

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

TRAIN_DIR = os.path.join(BASE_DIR, "training_images")

MODEL_PATH = os.path.join(BASE_DIR, "model.pkl")

CLASSES = [
    "healthy",
    "bacterial_blight",
    "early_blight",
    "leaf_rust",
    "powdery_mildew"
]

# =========================================================
# FEATURE EXTRACTION
# =========================================================

def extract_features(path):

    img = Image.open(path).convert("RGB").resize((64, 64))

    arr = np.array(img, dtype=np.float32)

    feats = []

    # color statistics
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

    # RGB ratios
    r = arr[:, :, 0]
    g = arr[:, :, 1]
    b = arr[:, :, 2]

    eps = 1e-6

    feats += [
        (g/(r+eps)).mean(),
        (r/(g+eps)).mean(),
        (b/(g+eps)).mean(),
    ]

    # histograms
    for c in range(3):

        h, _ = np.histogram(
            arr[:, :, c],
            bins=16,
            range=(0, 256)
        )

        feats += (h / h.sum()).tolist()

    # texture
    gray = arr.mean(axis=2)

    lap = (
        -gray[:-2,1:-1]
        -gray[2:,1:-1]
        -gray[1:-1,:-2]
        -gray[1:-1,2:]
        +4*gray[1:-1,1:-1]
    )

    feats += [
        lap.var(),
        lap.mean()
    ]

    return np.array(feats, dtype=np.float32)


# =========================================================
# LOAD DATASET
# =========================================================

def load_data():

    X = []
    y = []

    for cls in CLASSES:

        d = os.path.join(TRAIN_DIR, cls)

        if not os.path.exists(d):

            print(f"MISSING: {d}")

            continue

        files = [
            f for f in os.listdir(d)
            if f.lower().endswith((".png", ".jpg", ".jpeg"))
        ]

        print(f"{cls}: {len(files)} images")

        for f in files:

            try:

                fp = os.path.join(d, f)

                X.append(extract_features(fp))

                y.append(cls)

            except Exception as e:

                print(f"skip {f}: {e}")

    return np.array(X), np.array(y)


# =========================================================
# TRAIN
# =========================================================

if __name__ == "__main__":

    print("Loading dataset...\n")

    X, y = load_data()

    print(f"\nSamples: {len(X)}")
    print(f"Classes: {len(set(y))}")

    pipeline = Pipeline([
        ("scaler", StandardScaler()),

        ("svm", SVC(
            kernel="rbf",
            C=25,
            gamma="auto",
            probability=True,
            class_weight="balanced"
        ))
    ])

    print("\nRunning cross validation...\n")

    cv = cross_val_score(
        pipeline,
        X,
        y,
        cv=5
    )

    print(f"Accuracy: {cv.mean():.2%}")
    print(f"Std Dev : {cv.std():.2%}")

    print("\nTraining final model...\n")

    pipeline.fit(X, y)

    with open(MODEL_PATH, "wb") as f:

        pickle.dump({
            "pipeline": pipeline,
            "classes": CLASSES
        }, f)

    print(f"Model saved → {MODEL_PATH}")