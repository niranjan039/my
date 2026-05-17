@echo off
title PlantScan - Python AI API
color 0A
echo.
echo  ============================================
echo    PlantScan - Plant Disease Detection API
echo  ============================================
echo.
echo  [1/3] Installing required packages...
pip install flask Pillow numpy scikit-learn --quiet
echo  Done.
echo.
echo  [2/3] Generating training images...
python generate_training_data.py
echo.
echo  [3/3] Training AI model...
python train_model.py
echo.
echo  ============================================
echo    Starting API on http://localhost:5000
echo    Keep this window OPEN while using the app
echo  ============================================
echo.
python api.py
pause
