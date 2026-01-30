import sys
import json
import pandas as pd
import numpy as np
from datetime import datetime
try:
    from prophet import Prophet
    HAS_PROPHET = True
except ImportError:
    HAS_PROPHET = False

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "message": "No data file provided"}))
        return

    try:
        data_file = sys.argv[1]
        with open(data_file, 'r') as f:
            data = json.load(f)

        if not data:
            print(json.dumps({"success": False, "message": "Empty data provided"}))
            return

        # Convert to Pandas DataFrame
        df = pd.DataFrame(data)
        
        # Ensure correct types
        df['ds'] = pd.to_datetime(df['ds'])
        df['y'] = pd.to_numeric(df['y'])

        # Aggregate by day if multiple entries exist
        df = df.groupby('ds')['y'].sum().reset_index()

        if len(df) < 5:
            # Not enough data for Prophet, use simple linear regression or mean
            avg_y = df['y'].mean()
            print(json.dumps({
                "success": True, 
                "forecast_quantity": round(float(avg_y), 2),
                "calculation_method": "SIMPLE_MEAN",
                "confidence_score": 0.4,
                "note": "Insufficient data for Prophet"
            }))
            return

        if HAS_PROPHET:
            # Real Prophet Logic
            m = Prophet(yearly_seasonality=True, daily_seasonality=False, weekly_seasonality=True)
            m.fit(df)
            
            # Predict for next 30 days
            future = m.make_future_dataframe(periods=30)
            forecast = m.predict(future)
            
            # Get the last prediction (30 days from now)
            latest = forecast.iloc[-1]
            forecast_val = latest['yhat']
            
            # Ensure no negative values
            forecast_val = max(0, forecast_val)

            print(json.dumps({
                "success": True, 
                "forecast_quantity": round(float(forecast_val), 2),
                "calculation_method": "PROPHET",
                "confidence_score": 0.85
            }))
        else:
            # Fallback to a better statistical model than mean if Prophet is missing
            # Exponentially Weighted Moving Average (EMA)
            alpha = 0.3
            ema = df['y'].ewm(alpha=alpha).mean().iloc[-1]
            
            print(json.dumps({
                "success": True, 
                "forecast_quantity": round(float(ema), 2),
                "calculation_method": "EMA_STAT",
                "confidence_score": 0.6,
                "note": "Prophet library not found, using EMA fallback"
            }))

    except Exception as e:
        print(json.dumps({"success": False, "message": str(e)}))

if __name__ == "__main__":
    main()
