#!/usr/bin/env python3
import pandas as pd
import json

def test_excel_processing():
    """Test Excel data processing to verify our logic"""
    file_path = "/Users/izerkoen/Downloads/SATIŞ RAPORU-10.xlsx"
    
    try:
        # Read Excel file
        df = pd.read_excel(file_path, header=None)
        print(f"📊 Excel Shape: {df.shape}")
        
        # Show first few rows
        print("\n📋 First 10 rows:")
        for i in range(min(10, len(df))):
            print(f"Row {i}: {list(df.iloc[i])}")
        
        print(f"\n🔍 Data starting from row 2 (0-based):")
        data_rows = df.iloc[2:].copy()  # Skip row 0 (date) and row 1 (headers)
        
        # Column mapping
        columns = {
            0: 'belgeNo', 1: 'tarih', 2: 'depo', 3: 'musteri', 
            4: 'sube', 5: 'urun', 6: 'kod', 7: 'miktar', 
            8: 'birim', 9: 'net', 10: 'toplam', 11: 'paraBirimi'
        }
        
        valid_rows = 0
        dealers_found = []
        stores_found = set()
        currencies_found = set()
        total_amount = 0
        
        # Process first 50 rows for analysis (look for dealers)
        for i in range(min(50, len(data_rows))):
            row_data = data_rows.iloc[i]
            
            # Extract key fields
            depo = str(row_data.iloc[2] if pd.notna(row_data.iloc[2]) else '').strip()
            musteri = str(row_data.iloc[3] if pd.notna(row_data.iloc[3]) else '').strip()
            toplam = float(str(row_data.iloc[10]).replace(',', '.')) if pd.notna(row_data.iloc[10]) and str(row_data.iloc[10]).replace(',', '.').replace('-', '').replace('.', '').isdigit() else 0
            paraBirimi = str(row_data.iloc[11] if pd.notna(row_data.iloc[11]) else 'TL').strip()
            urun = str(row_data.iloc[5] if pd.notna(row_data.iloc[5]) else '').strip()
            
            print(f"\nRow {i+2}: Depo='{depo}', Musteri='{musteri}', Toplam={toplam}, Para='{paraBirimi}', Urun='{urun[:30]}...'")
            
            # Check for valid data
            if toplam > 0:
                valid_rows += 1
                total_amount += toplam
                
                # Track currencies
                if paraBirimi:
                    currencies_found.add(paraBirimi)
                
                # Check for dealers
                musteri_lower = musteri.lower()
                if 'ercem' in musteri_lower or 'akcan' in musteri_lower:
                    dealers_found.append(f"Ercem Akcan - {paraBirimi} - ₺{toplam}")
                elif 'mert' in musteri_lower or 'bikestop' in musteri_lower:
                    dealers_found.append(f"Mert Bikestop - {paraBirimi} - ₺{toplam}")
                
                # Track stores
                if depo and depo not in ['NaN', '']:
                    stores_found.add(depo)
                    print(f"   → Store: {depo}")
            else:
                print(f"   → Skipped (TOPLAM={toplam})")
        
        print(f"\n📈 Summary:")
        print(f"Valid rows: {valid_rows}/50")
        print(f"Total amount: ₺{total_amount:,.2f}")
        print(f"Stores found: {sorted(list(stores_found))}")
        print(f"Dealers found: {len(dealers_found)}")
        for dealer in dealers_found:
            print(f"  - {dealer}")
        print(f"Currencies: {sorted(list(currencies_found))}")
        
        return True
        
    except Exception as e:
        print(f"❌ Error: {e}")
        return False

if __name__ == "__main__":
    test_excel_processing()