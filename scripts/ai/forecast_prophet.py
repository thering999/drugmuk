import sys
import json
import logging

# Configure logging to stderr so it doesn't mess up stdout JSON
logging.basicConfig(level=logging.ERROR)

def main():
    try:
        if len(sys.argv) < 2:
            raise ValueError("No input file provided")

        input_file = sys.argv[1]
        
        with open(input_file, 'r') as f:
            data = json.load(f)

        if not data or len(data) < 5:
             # Not enough data for Prophet
             print(json.dumps({
                 "success": False,
                 "message": "Insufficient data points (recommend at least 5)"
             }))
             return

        # Try importing libraries. If missing, fail gracefully.
        try:
            import pandas as pd
            from prophet import Prophet
        except ImportError as e:
             # Fallback or error if libraries are missing
             print(json.dumps({
                 "success": False,
                 "message": f"Missing required libraries: {str(e)}. Please install pandas and prophet."
             }))
             return

        # Prepare DataFrame
        df = pd.DataFrame(data)
        df['ds'] = pd.to_datetime(df['ds'])
        df['y'] = pd.to_numeric(df['y'])

        # Initialize and Train Model
        m = Prophet(daily_seasonality=False, weekly_seasonality=False, yearly_seasonality=True)
        m.fit(df)

        # Make Future DataFrame (1 month ahead)
        future = m.make_future_dataframe(periods=30) 
        forecast = m.predict(future)

        # Get the prediction for the last date (target month)
        # We want the sum of the predicted values for the NEXT month.
        # But 'make_future_dataframe(periods=30)' adds days.
        # If input data was monthly, this might be tricky.
        # The PHP exportTrainingData uses 'dispense_date' which implies daily data.
        
        # Let's sum the last 30 days of the forecast to represent the next month's demand.
        next_month_forecast = forecast.tail(30)
        predicted_qty = next_month_forecast['yhat'].sum()
        
        # Calculate a simple confidence score based on uncertainty interval
        uncertainty = (next_month_forecast['yhat_upper'].sum() - next_month_forecast['yhat_lower'].sum())
        # Normalized confidence loosely based on uncertainty relative to value
        confidence = max(0.0, min(1.0, 1.0 - (uncertainty / (predicted_qty + 1))))

        result = {
            "success": True,
            "forecast_quantity": max(0, round(predicted_qty)),
            "calculation_method": "PROPHET",
            "confidence_score": round(confidence, 2)
        }

        print(json.dumps(result))

    except Exception as e:
        print(json.dumps({
            "success": False,
            "message": str(e)
        }))

if __name__ == "__main__":
    main()
