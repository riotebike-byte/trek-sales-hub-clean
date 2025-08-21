#!/usr/bin/env python3
import pandas as pd
import json

def read_dealer_list():
    """Read dealer list from Excel file"""
    file_path = "/Users/izerkoen/Downloads/CARİ RAPORU.xlsx"
    
    try:
        # Read Excel file
        df = pd.read_excel(file_path)
        print(f"📊 Excel Shape: {df.shape}")
        print(f"📋 Columns: {list(df.columns)}")
        
        # Show first 10 rows
        print("\n📋 First 10 rows:")
        for i in range(min(10, len(df))):
            print(f"Row {i}: {list(df.iloc[i])}")
        
        # Try to find dealer names column
        potential_name_cols = []
        for col in df.columns:
            col_str = str(col).lower()
            if any(keyword in col_str for keyword in ['cari', 'müşteri', 'bayi', 'ad', 'isim', 'name', 'firma']):
                potential_name_cols.append(col)
        
        print(f"\n🔍 Potential name columns: {potential_name_cols}")
        
        # Extract dealer names from most likely column
        if potential_name_cols:
            name_col = potential_name_cols[0]
            dealers = df[name_col].dropna().unique().tolist()
            print(f"\n🏢 Found {len(dealers)} unique dealers:")
            for i, dealer in enumerate(dealers[:20]):  # Show first 20
                print(f"  {i+1}. {dealer}")
            
            # Save to JSON for JavaScript
            dealer_data = {
                'dealers': dealers,
                'total_count': len(dealers),
                'source_column': str(name_col)
            }
            
            with open('dealer_list.json', 'w', encoding='utf-8') as f:
                json.dump(dealer_data, f, ensure_ascii=False, indent=2)
            
            print(f"\n✅ Dealer list saved to dealer_list.json")
            return dealer_data
            
        else:
            print("❌ Could not identify dealer name column")
            return None
        
    except Exception as e:
        print(f"❌ Error: {e}")
        return None

if __name__ == "__main__":
    read_dealer_list()