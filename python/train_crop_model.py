#!/usr/bin/env python3
"""
Crop Price Prediction Model Trainer
Trains ML models using CSV dataset for Laravel integration
"""

import pandas as pd
import numpy as np
import json
import pickle
import argparse
import os
import sys
import sklearn

from datetime import datetime
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler, LabelEncoder
from sklearn.svm import SVR
from sklearn.ensemble import RandomForestRegressor
from sklearn.linear_model import LinearRegression
from sklearn.metrics import mean_squared_error, mean_absolute_error, r2_score
import warnings
warnings.filterwarnings('ignore')

def save_with_compatibility(obj, filepath, protocol=4):
    """Save object with pickle using specified protocol for compatibility"""
    try:
        with open(filepath, 'wb') as f:
            pickle.dump(obj, f, protocol=protocol)
        return True
    except Exception as e:
        print(f"Error saving with protocol {protocol}: {str(e)}")
        return False

class CropPricePredictor:
    def __init__(self):
        self.models = {}
        self.scalers = {}
        self.encoders = {}
        self.feature_columns = []
        self.metrics = {}
        
    def load_and_preprocess_data(self, csv_path, commodity_id=None):
        """Load and preprocess the CSV dataset"""
        try:
            # Read CSV file
            print(f"Loading dataset from: {csv_path}")
            df = pd.read_csv(csv_path)
            print(f"Loaded dataset with {len(df)} records")
            
            # Filter by commodity if specified
            if commodity_id:
                original_count = len(df)
                df = df[df['commodity_id'] == commodity_id]
                print(f"Filtered to {len(df)} records for commodity_id: {commodity_id} (from {original_count})")
            
            if len(df) < 10:
                raise ValueError(f"Not enough data. Found {len(df)} records, need at least 10")
            
            # Convert date to datetime
            df['date'] = pd.to_datetime(df['date'])
            
            # Extract date features
            df['year'] = df['date'].dt.year
            df['month'] = df['date'].dt.month
            df['quarter'] = df['date'].dt.quarter
            df['day_of_year'] = df['date'].dt.dayofyear
            
            # Calculate rolling averages (price trends)
            df = df.sort_values(['commodity_id', 'market_id', 'date'])
            df['price_lag_1'] = df.groupby(['commodity_id', 'market_id'])['price'].shift(1)
            df['price_lag_3'] = df.groupby(['commodity_id', 'market_id'])['price'].shift(3)
            df['price_ma_3'] = df.groupby(['commodity_id', 'market_id'])['price'].transform(lambda x: x.rolling(3).mean())
            df['price_ma_6'] = df.groupby(['commodity_id', 'market_id'])['price'].transform(lambda x: x.rolling(6).mean())
  
            # Fill missing values
            df['price_lag_1'].fillna(df['price'], inplace=True)
            df['price_lag_3'].fillna(df['price'], inplace=True)
            df['price_ma_3'].fillna(df['price'], inplace=True)
            df['price_ma_6'].fillna(df['price'], inplace=True)
            
            # Create seasonal indicators
            df['is_harvest_season'] = df['month'].isin([6, 7, 8, 9]).astype(int)  # Adjust for Rwanda
            df['is_planting_season'] = df['month'].isin([10, 11, 12, 1]).astype(int)
            
            print(f"Preprocessing completed. Final dataset shape: {df.shape}")
            return df
            
        except Exception as e:
            print(f"Error loading data: {str(e)}")
            raise
    
    def prepare_features(self, df):
        """Prepare features for training"""
        print("Preparing features for training...")
        
        # Encode categorical variables
        categorical_cols = ['admin1', 'admin2', 'market', 'category', 'commodity', 'currency', 'pricetype']
        
        for col in categorical_cols:
            if col in df.columns:
                print(f"Encoding categorical column: {col}")
                le = LabelEncoder()
                # Convert to string to handle any data type
                df[f'{col}_encoded'] = le.fit_transform(df[col].astype(str))
                self.encoders[col] = le
                print(f"  - {col}: {len(le.classes_)} unique values")
        
        # Select features for training
        feature_cols = [
            'year', 'month', 'quarter', 'day_of_year',
            'latitude', 'longitude', 'market_id', 'commodity_id',
            'price_lag_1', 'price_lag_3', 'price_ma_3', 'price_ma_6',
            'is_harvest_season', 'is_planting_season'
        ]
        
        # Add encoded categorical features
        for col in categorical_cols:
            if col in df.columns:
                feature_cols.append(f'{col}_encoded')
        
        # Filter existing columns
        available_cols = [col for col in feature_cols if col in df.columns]
        missing_cols = [col for col in feature_cols if col not in df.columns]
        
        if missing_cols:
            print(f"Warning: Missing columns: {missing_cols}")
        
        self.feature_columns = available_cols
        print(f"Selected {len(available_cols)} features for training")
        
        return df[available_cols], df['price']
    
    def train_models(self, X, y, test_size=0.2):
        """Train multiple models and select the best one"""
        print(f"\nTraining models with {X.shape[0]} samples and {X.shape[1]} features...")
        
        # Split data
        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=test_size, random_state=42, shuffle=True
        )
        
        print(f"Train set: {X_train.shape[0]} samples")
        print(f"Test set: {X_test.shape[0]} samples")
        
        # Scale features
        scaler = StandardScaler()
        X_train_scaled = scaler.fit_transform(X_train)
        X_test_scaled = scaler.transform(X_test)
        self.scalers['feature_scaler'] = scaler
        
        # Define models to train
        models_to_train = {
            'svr_linear': SVR(kernel='linear', C=100, epsilon=0.1),
            'svr_rbf': SVR(kernel='rbf', C=100, gamma='scale', epsilon=0.1),
            'random_forest': RandomForestRegressor(n_estimators=100, random_state=42),
            'linear_regression': LinearRegression()
        }
        
        best_model = None
        best_score = -np.inf
        best_model_name = None
        
        print("\nTraining models...")
        for name, model in models_to_train.items():
            try:
                print(f"Training {name}...")
                
                # Train model
                if 'svr' in name or 'linear' in name:
                    model.fit(X_train_scaled, y_train)
                    predictions = model.predict(X_test_scaled)
                else:
                    model.fit(X_train, y_train)
                    predictions = model.predict(X_test)
                
                # Calculate metrics
                mse = mean_squared_error(y_test, predictions)
                mae = mean_absolute_error(y_test, predictions)
                r2 = r2_score(y_test, predictions)
                
                self.metrics[name] = {
                    'mse': float(mse),
                    'mae': float(mae),
                    'r2': float(r2),
                    'accuracy_percentage': float(max(0, r2 * 100)),
                    'training_samples': len(X_train),
                    'test_samples': len(X_test)
                }
                
                print(f"  {name}: R² = {r2:.4f}, MAE = {mae:.2f}, MSE = {mse:.2f}")
                
                # Select best model based on R²
                if r2 > best_score:
                    best_score = r2
                    best_model = model
                    best_model_name = name
                
                self.models[name] = model
                
            except Exception as e:
                print(f"Error training {name}: {str(e)}")
        
        if best_model is None:
            raise ValueError("No model was successfully trained")
        
        print(f"\nBest model: {best_model_name} (R² = {best_score:.4f})")
        return best_model_name, best_model
    
    def save_model(self, model, model_name, commodity_id, output_dir):
        """Save the trained model and metadata with improved compatibility"""
        os.makedirs(output_dir, exist_ok=True)
        
        print(f"\nSaving model to directory: {output_dir}")
        
        # Try different pickle protocols for compatibility
        protocols_to_try = [4, 3, 2]
        
        # Save model
        model_path = os.path.join(output_dir, f'crop_{commodity_id}_{model_name}.pkl')
        model_saved = False
        
        for protocol in protocols_to_try:
            if save_with_compatibility(model, model_path, protocol):
                print(f"Model saved with pickle protocol {protocol}: {model_path}")
                model_saved = True
                break
        
        if not model_saved:
            raise Exception("Failed to save model with any pickle protocol")
        
        # Save scalers and encoders
        metadata_path = os.path.join(output_dir, f'crop_{commodity_id}_metadata.pkl')
        metadata = {
            'scalers': self.scalers,
            'encoders': self.encoders,
            'feature_columns': self.feature_columns,
            'model_name': model_name,
            'sklearn_version': sklearn.__version__,
            'pandas_version': pd.__version__,
            'numpy_version': np.__version__
        }
        
        metadata_saved = False
        for protocol in protocols_to_try:
            if save_with_compatibility(metadata, metadata_path, protocol):
                print(f"Metadata saved with pickle protocol {protocol}: {metadata_path}")
                metadata_saved = True
                break
        
        if not metadata_saved:
            raise Exception("Failed to save metadata with any pickle protocol")
        
        # Save metrics as JSON (more portable)
        metrics_path = os.path.join(output_dir, f'crop_{commodity_id}_metrics.json')
        final_metrics = self.metrics[model_name].copy()
        final_metrics.update({
            'trained_at': datetime.now().isoformat(),
            'model_type': model_name,
            'feature_count': len(self.feature_columns),
            'features_used': self.feature_columns,
            'sklearn_version': sklearn.__version__,
            'pandas_version': pd.__version__,
            'numpy_version': np.__version__
        })
        
        with open(metrics_path, 'w') as f:
            json.dump(final_metrics, f, indent=2)
        print(f"Metrics saved: {metrics_path}")
        
        # Save training data summary
        summary_path = os.path.join(output_dir, f'crop_{commodity_id}_training_summary.csv')
        summary_data = []
        for name, metrics in self.metrics.items():
            summary_data.append({
                'model': name,
                'r2_score': metrics['r2'],
                'accuracy_percentage': metrics['accuracy_percentage'],
                'mae': metrics['mae'],
                'mse': metrics['mse']
            })
        
        pd.DataFrame(summary_data).to_csv(summary_path, index=False)
        print(f"Training summary saved: {summary_path}")
        
        return {
            'model_path': model_path,
            'metadata_path': metadata_path,
            'metrics_path': metrics_path,
            'accuracy': final_metrics['accuracy_percentage']
        }

def main():
    parser = argparse.ArgumentParser(description='Train crop price prediction model')
    parser.add_argument('--dataset', required=True, help='Path to CSV dataset')
    parser.add_argument('--commodity-id', type=int, help='Specific commodity ID to train on')
    parser.add_argument('--output', default='../storage/app/models', help='Output directory for models')
    parser.add_argument('--test-size', type=float, default=0.2, help='Test size for train/test split')
    
    args = parser.parse_args()
    
    try:
        print("Starting crop price prediction model training...")
        print(f"Python version: {sys.version}")
        print(f"Dataset: {args.dataset}")
        print(f"Commodity ID: {args.commodity_id}")
        print(f"Output directory: {args.output}")
        
        # Check if dataset exists
        if not os.path.exists(args.dataset):
            raise FileNotFoundError(f"Dataset file not found: {args.dataset}")
        
        # Initialize predictor
        predictor = CropPricePredictor()
        
        # Load and preprocess data
        df = predictor.load_and_preprocess_data(args.dataset, args.commodity_id)
        
        # Prepare features
        X, y = predictor.prepare_features(df)
        print(f"Features prepared: {X.shape[1]} features, {X.shape[0]} samples")
        print(f"Feature columns: {predictor.feature_columns}")
        print(f"Price range: {y.min():.2f} - {y.max():.2f}")
        
        # Train models
        best_model_name, best_model = predictor.train_models(X, y, args.test_size)
        
        # Save model
        result = predictor.save_model(
            best_model, 
            best_model_name, 
            args.commodity_id or 'all', 
            args.output
        )
        
        print(f"\nTraining completed successfully!")
        print(f"Best model accuracy: {result['accuracy']:.2f}%")
        
        # Output JSON for Laravel to parse
        output_json = {
            'success': True,
            'model_name': best_model_name,
            'accuracy': result['accuracy'],
            'model_path': result['model_path'],
            'metrics_path': result['metrics_path'],
            'message': f'Model trained successfully with {result["accuracy"]:.2f}% accuracy'
        }
        
        print("\n" + "="*50)
        print("TRAINING RESULT (JSON):")
        print(json.dumps(output_json, indent=2))
        
    except Exception as e:
        error_output = {
            'success': False,
            'error': str(e),
            'message': f'Training failed: {str(e)}'
        }
        print("\n" + "="*50)
        print("TRAINING ERROR (JSON):")
        print(json.dumps(error_output, indent=2))
        sys.exit(1)

if __name__ == "__main__":
    main()