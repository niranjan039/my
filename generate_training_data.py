"""
Generate synthetic plant disease training images.
Run:
    python generate_training_data.py
"""

from PIL import Image, ImageDraw, ImageFilter
import numpy as np
import os
import math
import random
import shutil

random.seed(42)
np.random.seed(42)

# =========================================================
# CLASS DEFINITIONS
# =========================================================

CLASSES = {

    # Healthy leaf
    "healthy": {
        "base": (55, 140, 55),
        "spot": None,
        "n": 120
    },

    # Yellow/brown angular lesions
    "bacterial_blight": {
        "base": (40, 125, 40),
        "spot": (170, 120, 40),
        "n": 120
    },

    # Dark brown circular lesions
    "early_blight": {
        "base": (45, 120, 45),
        "spot": (90, 45, 20),
        "n": 120
    },

    # Orange rust pustules
    "leaf_rust": {
        "base": (50, 120, 40),
        "spot": (220, 110, 30),
        "n": 120
    },

    # White powdery coating
    "powdery_mildew": {
        "base": (45, 120, 45),
        "spot": (245, 245, 245),
        "n": 120
    },
}

BASE_DIR = os.path.join(
    os.path.dirname(os.path.abspath(__file__)),
    "training_images"
)

# =========================================================
# LEAF MASK
# =========================================================

def leaf_mask(size=128):

    img = Image.new("L", (size, size), 0)
    d = ImageDraw.Draw(img)

    m = size // 8

    d.ellipse(
        [m, m//2, size-m, size-m//2],
        fill=255
    )

    return np.array(img) / 255.0


# =========================================================
# NOISE
# =========================================================

def noise(arr, s=12):

    n = np.random.randint(
        -s,
        s,
        arr.shape,
        dtype=np.int16
    )

    return np.clip(
        arr.astype(np.int16) + n,
        0,
        255
    ).astype(np.uint8)


# =========================================================
# CREATE LEAF
# =========================================================

def make_leaf(cls, cfg):

    sz = 128

    base = cfg["base"]

    arr = np.full(
        (sz, sz, 3),
        base,
        dtype=np.uint8
    )

    arr = noise(arr, 15)

    img = Image.fromarray(arr)

    draw = ImageDraw.Draw(img)

    # veins
    vein_color = tuple(max(0, c-35) for c in base)

    cx, cy = sz//2, sz//2

    for a in [0, 25, -25, 50, -50, 70, -70]:

        r = math.radians(a)

        ex = int(cx + 52 * math.sin(r))
        ey = int(cy - 52 * math.cos(r))

        draw.line(
            [(cx, cy), (ex, ey)],
            fill=vein_color,
            width=2
        )

    arr = np.array(img)

    # =====================================================
    # DISEASE PATTERNS
    # =====================================================

    if cfg["spot"]:

        sc = cfg["spot"]

        # -------------------------------------------------
        # POWDERY MILDEW
        # -------------------------------------------------

        if cls == "powdery_mildew":

            for _ in range(random.randint(4, 7)):

                sx = random.randint(25, sz-25)
                sy = random.randint(25, sz-25)

                radius = random.randint(12, 20)

                for y in range(max(0, sy-radius), min(sz, sy+radius)):
                    for x in range(max(0, sx-radius), min(sz, sx+radius)):

                        d = ((x-sx)**2 + (y-sy)**2)**0.5

                        if d < radius:

                            alpha = random.uniform(0.10, 0.30)

                            arr[y, x] = np.clip(
                                arr[y, x]*(1-alpha) + np.array(sc)*alpha,
                                0,
                                255
                            ).astype(np.uint8)

        # -------------------------------------------------
        # LEAF RUST
        # -------------------------------------------------

        elif cls == "leaf_rust":

            for _ in range(random.randint(40, 80)):

                sx = random.randint(10, sz-10)
                sy = random.randint(10, sz-10)

                radius = random.randint(2, 5)

                for y in range(max(0, sy-radius), min(sz, sy+radius)):
                    for x in range(max(0, sx-radius), min(sz, sx+radius)):

                        d = ((x-sx)**2 + (y-sy)**2)**0.5

                        if d < radius:

                            arr[y, x] = np.clip(
                                np.array(sc) + random.randint(-15, 15),
                                0,
                                255
                            )

        # -------------------------------------------------
        # EARLY BLIGHT
        # -------------------------------------------------

        elif cls == "early_blight":

            for _ in range(random.randint(30, 60)):

                sx = random.randint(20, sz-20)
                sy = random.randint(20, sz-20)

                radius = random.randint(8, 16)

                for y in range(max(0, sy-radius), min(sz, sy+radius)):
                    for x in range(max(0, sx-radius), min(sz, sx+radius)):

                        d = ((x-sx)**2 + (y-sy)**2)**0.5

                        if d < radius:

                            arr[y, x] = np.clip(
                                np.array(sc) + random.randint(-10, 10),
                                0,
                                255
                            )

        # -------------------------------------------------
        # BACTERIAL BLIGHT
        # -------------------------------------------------

        elif cls == "bacterial_blight":

            for _ in range(random.randint(8, 15)):

                sx = random.randint(20, sz-20)
                sy = random.randint(20, sz-20)

                rw = random.randint(8, 18)
                rh = random.randint(4, 10)

                for y in range(max(0, sy-rh), min(sz, sy+rh)):
                    for x in range(max(0, sx-rw), min(sz, sx+rw)):

                        if random.random() > 0.25:

                            arr[y, x] = sc

    # =====================================================
    # APPLY LEAF MASK
    # =====================================================

    mask = leaf_mask(sz)

    bg = np.full(
        (sz, sz, 3),
        (195, 210, 188),
        dtype=np.uint8
    )

    m3 = mask[:, :, np.newaxis]

    final = (arr * m3 + bg * (1 - m3)).astype(np.uint8)

    return Image.fromarray(final).filter(
        ImageFilter.GaussianBlur(0.5)
    )


# =========================================================
# MAIN
# =========================================================

if __name__ == "__main__":

    print("Removing old dataset...")

    if os.path.exists(BASE_DIR):
        shutil.rmtree(BASE_DIR)

    os.makedirs(BASE_DIR, exist_ok=True)

    print("Generating training images...\n")

    total = 0

    for cls, cfg in CLASSES.items():

        d = os.path.join(BASE_DIR, cls)

        os.makedirs(d, exist_ok=True)

        for i in range(cfg["n"]):

            img = make_leaf(cls, cfg)

            img.save(
                os.path.join(
                    d,
                    f"{cls}_{i:03d}.png"
                )
            )

            total += 1

        print(f"✓ {cls}: {cfg['n']} images")

    print(f"\nDone — {total} images generated.")