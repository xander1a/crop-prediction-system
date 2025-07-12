#!/usr/bin/env python3
"""
Simple test script to verify the prediction works
"""

import json
import os
import subprocess
import sys

def create_test_input():
    """Create a test input file"""
    test_data = {
        "latitude": -1.95,
        "longitude": 30.06,
        "market_id": 1076,
        "commodity_id": 51,
        "price_lag_1": 200.0,
        "price_lag_3": 195.0,
        "price_ma_3": 198.0,
        "price_ma_6": 202.0,
        "admin1": "Kigali City",
        "admin2": "Nyarugenge",
        "market": "Kigali",
        "category": "cereals and tubers",
        "commodity": "Maize",
        "currency": "RWF",
        "pricetype": "Wholesale",
        "unit": "KG",
        "target_date": "2025-06-28"
    }
    
    with open('test_input.json', 'w') as f:
        json.dump(test_data, f, indent=2)
    
    print("Created test_input.json")
    return 'test_input.json'

def test_prediction():
    """Test the prediction script"""
    print("Testing crop price prediction...")
    
    # Create test input
    input_file = create_test_input()
    
    # Check if model files exist
    model_files = [
        'storage/app/models/crop_51_svr_linear.pkl',
        'storage/app/models/crop_51_metadata.pkl'
    ]
    
    for file_path in model_files:
        if not os.path.exists(file_path):
            print(f"ERROR: Model file not found: {file_path}")
            return False
    
    # Run prediction
    cmd = [
        'python',
        'python/predict_crop_price.py',
        '--model', 'storage/app/models/crop_51_svr_linear.pkl',
        '--metadata', 'storage/app/models/crop_51_metadata.pkl',
        '--input', input_file,
        '--debug'
    ]
    
    print(f"Running command: {' '.join(cmd)}")
    
    try:
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=60)
        
        print(f"Return code: {result.returncode}")
        print(f"STDOUT:\n{result.stdout}")
        print(f"STDERR:\n{result.stderr}")
        
        if result.returncode == 0:
            print("✅ Prediction successful!")
            return True
        else:
            print("❌ Prediction failed!")
            return False
            
    except subprocess.TimeoutExpired:
        print("❌ Prediction timed out!")
        return False
    except Exception as e:
        print(f"❌ Exception: {e}")
        return False
    finally:
        # Clean up
        if os.path.exists(input_file):
            os.remove(input_file)

if __name__ == "__main__":
    success = test_prediction()
    sys.exit(0 if success else 1)