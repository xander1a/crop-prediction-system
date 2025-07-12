#!/usr/bin/env python3
"""
Crop Price Prediction Script
Makes predictions using trained models for Laravel integration
"""

import pandas as pd
import numpy as np
import json
import pickle
import argparse
import os
import sys
from datetime import datetime
import warnings
warnings.filterwarnings('ignore')

# Add debug information
def print_debug(message):
    print(f"DEBUG: {message}", file=sys.stderr)

def check_dependencies():
    """Check if all required dependencies are available"""
    try:
        import sklearn
        print_debug(f"scikit-learn version: {sklearn.__version__}")
        
        import pandas as pd
        print_debug(f"pandas version: {pd.__version__}")
        
        import numpy as np
        print_debug(f"numpy version: {np.__version__}")
        
        return True
    except ImportError as e:
        print_debug(f"Missing dependency: {e}")
        return False

class CropPricePredictor:
    def __init__(self):
        self.model = None
        self.scalers = {}
        self.encoders = {}
        self.feature_columns = []
        self.model_name = None
        
    def load_model(self, model_path, metadata_path):
        """Load trained model and metadata with improved error handling"""
        try:
            print_debug(f"Attempting to load model from: {model_path}")
            print_debug(f"Attempting to load metadata from: {metadata_path}")
            
            # Check if files exist
            if not os.path.exists(model_path):
                raise FileNotFoundError(f"Model file not found: {model_path}")
            
            if not os.path.exists(metadata_path):
                raise FileNotFoundError(f"Metadata file not found: {metadata_path}")
            
            # Check file sizes
            model_size = os.path.getsize(model_path)
            metadata_size = os.path.getsize(metadata_path)
            print_debug(f"Model file size: {model_size} bytes")
            print_debug(f"Metadata file size: {metadata_size} bytes")
            
            if model_size == 0 or metadata_size == 0:
                raise ValueError("Model or metadata file is empty")
            
            # Try different pickle protocols
            protocols_to_try = [None, 4, 3, 2]
            
            for protocol in protocols_to_try:
                try:
                    print_debug(f"Trying to load with pickle protocol: {protocol}")
                    
                    # Load model
                    with open(model_path, 'rb') as f:
                        self.model = pickle.load(f)
                    
                    print_debug("Model loaded successfully")
                    
                    # Load metadata
                    with open(metadata_path, 'rb') as f:
                        metadata = pickle.load(f)
                    
                    print_debug("Metadata loaded successfully")
                    
                    # Extract metadata
                    self.scalers = metadata.get('scalers', {})
                    self.encoders = metadata.get('encoders', {})
                    self.feature_columns = metadata.get('feature_columns', [])
                    self.model_name = metadata.get('model_name', 'unknown')
                    
                    print_debug(f"Model type: {type(self.model)}")
                    print_debug(f"Model name: {self.model_name}")
                    print_debug(f"Feature columns count: {len(self.feature_columns)}")
                    print_debug(f"Encoders count: {len(self.encoders)}")
                    print_debug(f"Scalers count: {len(self.scalers)}")
                    
                    print(f"Model loaded successfully: {self.model_name}")
                    return True
                    
                except Exception as e:
                    print_debug(f"Failed with protocol {protocol}: {str(e)}")
                    continue
            
            raise Exception("Failed to load model with any pickle protocol")
            
        except Exception as e:
            print_debug(f"Error in load_model: {str(e)}")
            print(f"Error loading model: {str(e)}")
            return False
    
    def prepare_prediction_features(self, input_data):
        """Prepare features for prediction from input data"""
        try:
            print_debug("Preparing prediction features")
            
            # Create base feature dictionary
            features = {}
            
            # Date features
            target_date = datetime.strptime(input_data['target_date'], '%Y-%m-%d')
            features['year'] = target_date.year
            features['month'] = target_date.month
            features['quarter'] = (target_date.month - 1) // 3 + 1
            features['day_of_year'] = target_date.timetuple().tm_yday
            
            print_debug(f"Date features: year={features['year']}, month={features['month']}")
            
            # Location features
            features['latitude'] = float(input_data.get('latitude', -1.95))
            features['longitude'] = float(input_data.get('longitude', 30.06))
            features['market_id'] = int(input_data.get('market_id', 1076))
            features['commodity_id'] = int(input_data['commodity_id'])
            
            print_debug(f"Location features: lat={features['latitude']}, lon={features['longitude']}")
            
            # Seasonal features
            features['is_harvest_season'] = 1 if target_date.month in [6, 7, 8, 9] else 0
            features['is_planting_season'] = 1 if target_date.month in [10, 11, 12, 1] else 0
            
            # Historical price features (use provided or defaults)
            features['price_lag_1'] = float(input_data.get('price_lag_1', input_data.get('recent_price', 200.0)))
            features['price_lag_3'] = float(input_data.get('price_lag_3', input_data.get('price_3_months_ago', 200.0)))
            features['price_ma_3'] = float(input_data.get('price_ma_3', input_data.get('price_avg_3_months', 200.0)))
            features['price_ma_6'] = float(input_data.get('price_ma_6', input_data.get('price_avg_6_months', 200.0)))
            
            print_debug(f"Price features: lag1={features['price_lag_1']}, lag3={features['price_lag_3']}")
            
            # Encode categorical features
            categorical_mappings = {
                'admin1': input_data.get('admin1', 'Kigali City'),
                'admin2': input_data.get('admin2', 'Nyarugenge'),
                'market': input_data.get('market', 'Kigali'),
                'category': input_data.get('category', 'cereals and tubers'),
                'commodity': input_data.get('commodity', 'Maize'),
                'currency': input_data.get('currency', 'RWF'),
                'pricetype': input_data.get('pricetype', 'Wholesale')
            }
            
            print_debug(f"Categorical mappings: {categorical_mappings}")
            
            for col, value in categorical_mappings.items():
                if col in self.encoders:
                    try:
                        # Try to encode the value
                        encoded_value = self.encoders[col].transform([str(value)])[0]
                        features[f'{col}_encoded'] = encoded_value
                        print_debug(f"Encoded {col}: {value} -> {encoded_value}")
                    except ValueError as e:
                        # If value not seen during training, use most common class (usually 0)
                        features[f'{col}_encoded'] = 0
                        print_debug(f"Unknown value '{value}' for {col}, using default: {str(e)}")
                else:
                    print_debug(f"No encoder found for {col}")
            
            # Create feature vector in the same order as training
            feature_vector = []
            missing_features = []
            
            for col in self.feature_columns:
                if col in features:
                    feature_vector.append(features[col])
                else:
                    feature_vector.append(0)  # Default value for missing features
                    missing_features.append(col)
            
            if missing_features:
                print_debug(f"Missing features (using defaults): {missing_features}")
            
            print_debug(f"Feature vector length: {len(feature_vector)}")
            print_debug(f"Expected features: {len(self.feature_columns)}")
            
            return np.array(feature_vector).reshape(1, -1)
            
        except Exception as e:
            print_debug(f"Error in prepare_prediction_features: {str(e)}")
            print(f"Error preparing features: {str(e)}")
            raise
    
    def predict_price(self, input_data):
        """Make price prediction"""
        try:
            print_debug("Starting price prediction")
            
            # Prepare features
            X = self.prepare_prediction_features(input_data)
            print_debug(f"Features prepared, shape: {X.shape}")
            
            # Scale features if needed
            if 'svr' in self.model_name.lower() or 'linear' in self.model_name.lower():
                if 'feature_scaler' in self.scalers:
                    print_debug("Applying feature scaling")
                    X = self.scalers['feature_scaler'].transform(X)
                else:
                    print_debug("No feature scaler found, but model expects scaling")
            
            # Make prediction
            print_debug("Making prediction with model")
            predicted_price = self.model.predict(X)[0]
            print_debug(f"Raw prediction: {predicted_price}")
            
            # Ensure positive price
            predicted_price = max(0, predicted_price)
            print_debug(f"Final prediction: {predicted_price}")
            
            return float(predicted_price)
            
        except Exception as e:
            print_debug(f"Error in predict_price: {str(e)}")
            print(f"Error making prediction: {str(e)}")
            raise

def main():
    parser = argparse.ArgumentParser(description='Predict crop price using trained model')
    parser.add_argument('--model', required=True, help='Path to trained model file')
    parser.add_argument('--metadata', required=True, help='Path to model metadata file')
    parser.add_argument('--input', required=True, help='Input JSON file or JSON string')
    parser.add_argument('--output', help='Output file path (optional)')
    parser.add_argument('--debug', action='store_true', help='Enable debug output')
    
    args = parser.parse_args()
    
    try:
        print_debug("Starting crop price prediction")
        print_debug(f"Python version: {sys.version}")
        print_debug(f"Current working directory: {os.getcwd()}")
        print_debug(f"Arguments: {vars(args)}")
        
        # Check dependencies
        if not check_dependencies():
            raise Exception("Missing required dependencies")
        
        # Initialize predictor
        predictor = CropPricePredictor()
        
        # Load model
        if not predictor.load_model(args.model, args.metadata):
            raise Exception("Failed to load model")
        
        # Parse input data with better error handling
        try:
            print_debug(f"Input argument: '{args.input}'")
            
            # First, always check if it's a file path (even if the file doesn't exist yet)
            # Check by looking at the string pattern
            input_str = args.input.strip()
            
            # If it looks like a file path or ends with .json, treat as file
            if (os.path.sep in input_str or 
                input_str.endswith('.json') or 
                input_str.endswith('.txt') or
                not input_str.startswith('{')):
                
                print_debug(f"Treating input as file path: {input_str}")
                
                if not os.path.isfile(input_str):
                    raise Exception(f"Input file does not exist: {input_str}")
                
                # Check file size
                file_size = os.path.getsize(input_str)
                print_debug(f"Input file size: {file_size} bytes")
                
                if file_size == 0:
                    raise Exception("Input file is empty")
                
                with open(input_str, 'r', encoding='utf-8') as f:
                    file_content = f.read()
                    print_debug(f"File content length: {len(file_content)}")
                    print_debug(f"File content preview: {file_content[:200]}")
                    
                    if not file_content.strip():
                        raise Exception("Input file contains no data")
                    
                    input_data = json.loads(file_content)
            else:
                print_debug("Parsing input as JSON string")
                if not input_str:
                    raise Exception("Input string is empty")
                input_data = json.loads(input_str)
                
            print_debug(f"Input data keys: {list(input_data.keys())}")
            print_debug(f"Input data sample: {dict(list(input_data.items())[:5])}")
            
            # Validate required fields
            required_fields = ['commodity_id', 'target_date']
            missing_fields = [field for field in required_fields if field not in input_data]
            if missing_fields:
                raise Exception(f"Missing required fields: {missing_fields}")
            
        except json.JSONDecodeError as e:
            print_debug(f"JSON decode error: {str(e)}")
            print_debug(f"Input content: '{args.input}'")
            raise Exception(f"Invalid JSON format: {str(e)}")
        except Exception as e:
            print_debug(f"Error parsing input: {str(e)}")
            raise Exception(f"Error reading input: {str(e)}")
        
        print(f"Making prediction for commodity_id: {input_data.get('commodity_id')}")
        print(f"Target date: {input_data.get('target_date')}")
        
        # Make prediction
        predicted_price = predictor.predict_price(input_data)
        
        # Load model metrics for confidence score
        metrics_path = args.metadata.replace('_metadata.pkl', '_metrics.json')
        confidence = None
        if os.path.exists(metrics_path):
            try:
                with open(metrics_path, 'r') as f:
                    metrics = json.load(f)
                    confidence = metrics.get('accuracy_percentage')
                print_debug(f"Loaded confidence score: {confidence}")
            except Exception as e:
                print_debug(f"Could not load metrics: {str(e)}")
        
        # Prepare result
        result = {
            'success': True,
            'predicted_price': round(predicted_price, 2),
            'confidence_score': confidence,
            'model_used': predictor.model_name,
            'prediction_date': datetime.now().isoformat(),
            'target_date': input_data['target_date'],
            'commodity_id': input_data['commodity_id'],
            'admin1': input_data.get('admin1'),
            'admin2': input_data.get('admin2'),
            'market': input_data.get('market'),
            'crop': input_data.get('commodity', 'Unknown'),
        }
        
        # Save result if output path provided
        if args.output:
            with open(args.output, 'w') as f:
                json.dump(result, f, indent=2)
        
        print("\n" + "="*50)
        print("PREDICTION RESULT (JSON):")
        print(json.dumps(result, indent=2))
        
    except Exception as e:
        print_debug(f"Main exception: {str(e)}")
        error_result = {
            'success': False,
            'error': str(e),
            'predicted_price': None,
            'confidence_score': None
        }
        
        print("\n" + "="*50)
        print("PREDICTION ERROR (JSON):")
        print(json.dumps(error_result, indent=2))
        sys.exit(1)

if __name__ == "__main__":
    main()