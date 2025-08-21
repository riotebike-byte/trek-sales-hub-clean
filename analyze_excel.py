#!/usr/bin/env python3
import pandas as pd
import sys
import json
import os

def analyze_excel_file(file_path):
    """Analyze Excel file structure and content"""
    try:
        # Read Excel file
        print(f"📊 Analyzing Excel file: {file_path}")
        
        # Get all sheet names
        xl_file = pd.ExcelFile(file_path)
        sheet_names = xl_file.sheet_names
        print(f"📋 Sheets found: {sheet_names}")
        
        results = {
            'file_info': {
                'path': file_path,
                'sheets': sheet_names
            },
            'sheet_analysis': {}
        }
        
        # Analyze each sheet
        for sheet_name in sheet_names:
            print(f"\n🔍 Analyzing sheet: {sheet_name}")
            
            try:
                # Read sheet
                df = pd.read_excel(file_path, sheet_name=sheet_name)
                
                # Basic info
                sheet_info = {
                    'shape': df.shape,
                    'columns': list(df.columns),
                    'column_types': {col: str(df[col].dtype) for col in df.columns},
                    'sample_data': df.head(10).to_dict('records') if len(df) > 0 else [],
                    'summary': {
                        'total_rows': len(df),
                        'total_columns': len(df.columns),
                        'has_data': len(df) > 0
                    }
                }
                
                # Look for key columns that might indicate sales data
                sales_indicators = []
                for col in df.columns:
                    col_lower = str(col).lower()
                    if any(keyword in col_lower for keyword in ['satış', 'sales', 'amount', 'tutar', 'fiyat', 'price', 'total', 'toplam']):
                        sales_indicators.append(col)
                
                store_indicators = []
                for col in df.columns:
                    col_lower = str(col).lower()
                    if any(keyword in col_lower for keyword in ['mağaza', 'store', 'depo', 'warehouse', 'şube', 'branch']):
                        store_indicators.append(col)
                
                date_indicators = []
                for col in df.columns:
                    col_lower = str(col).lower()
                    if any(keyword in col_lower for keyword in ['tarih', 'date', 'ay', 'month', 'yıl', 'year']):
                        date_indicators.append(col)
                
                sheet_info['indicators'] = {
                    'sales_columns': sales_indicators,
                    'store_columns': store_indicators,
                    'date_columns': date_indicators
                }
                
                # Try to detect currency and numeric columns
                numeric_cols = []
                for col in df.columns:
                    if df[col].dtype in ['int64', 'float64']:
                        numeric_cols.append(col)
                    elif df[col].dtype == 'object':
                        # Check if string column contains numeric values
                        sample_values = df[col].dropna().head(5).astype(str)
                        if any(any(char.isdigit() for char in str(val)) for val in sample_values):
                            numeric_cols.append(col)
                
                sheet_info['numeric_columns'] = numeric_cols
                
                # Sample unique values for categorical columns
                categorical_samples = {}
                for col in df.columns:
                    if df[col].dtype == 'object' and len(df[col].unique()) < 20:
                        categorical_samples[col] = list(df[col].unique()[:10])
                
                sheet_info['categorical_samples'] = categorical_samples
                
                results['sheet_analysis'][sheet_name] = sheet_info
                
                print(f"   📏 Shape: {df.shape}")
                print(f"   📊 Columns: {list(df.columns)}")
                print(f"   💰 Sales indicators: {sales_indicators}")
                print(f"   🏪 Store indicators: {store_indicators}")
                print(f"   📅 Date indicators: {date_indicators}")
                print(f"   🔢 Numeric columns: {numeric_cols}")
                
            except Exception as e:
                print(f"   ❌ Error reading sheet {sheet_name}: {e}")
                results['sheet_analysis'][sheet_name] = {
                    'error': str(e)
                }
        
        return results
        
    except Exception as e:
        print(f"❌ Error analyzing file: {e}")
        return {'error': str(e)}

if __name__ == "__main__":
    file_path = "/Users/izerkoen/Downloads/SATIŞ RAPORU-10.xlsx"
    
    if not os.path.exists(file_path):
        print(f"❌ File not found: {file_path}")
        sys.exit(1)
    
    results = analyze_excel_file(file_path)
    
    # Save detailed analysis to JSON
    output_file = "/Users/izerkoen/sales-league-clean/excel_analysis.json"
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(results, f, indent=2, ensure_ascii=False)
    
    print(f"\n✅ Analysis complete. Results saved to: {output_file}")