#!/usr/bin/env python3
import json

def clean_dealer_list():
    """Clean the dealer list by removing invalid entries"""
    
    # Read the current dealer list
    with open('dealer_list.json', 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    original_dealers = data['dealers']
    print(f"Original dealer count: {len(original_dealers)}")
    
    # Filter out invalid entries
    clean_dealers = []
    for dealer in original_dealers:
        if not dealer or not isinstance(dealer, str):
            continue
            
        # Skip date entries
        import re
        if re.match(r'^\d{2}\.\d{2}\.\d{4}$', dealer):
            print(f"Skipping date: {dealer}")
            continue
            
        # Skip header entries
        if 'İsim/Unvan' in dealer or 'Unnamed:' in dealer:
            print(f"Skipping header: {dealer}")
            continue
            
        # Must have reasonable length (at least 5 characters)
        if len(dealer.strip()) < 5:
            print(f"Skipping short: {dealer}")
            continue
            
        clean_dealers.append(dealer.strip())
    
    print(f"Clean dealer count: {len(clean_dealers)}")
    print("\nFirst 10 clean dealers:")
    for i, dealer in enumerate(clean_dealers[:10]):
        print(f"  {i+1}. {dealer}")
    
    # Save the clean list
    clean_data = {
        'dealers': clean_dealers,
        'total_count': len(clean_dealers),
        'source_column': data.get('source_column', 'CARİ RAPORU')
    }
    
    with open('dealer_list.json', 'w', encoding='utf-8') as f:
        json.dump(clean_data, f, ensure_ascii=False, indent=2)
    
    print(f"\n✅ Cleaned dealer list saved: {len(clean_dealers)} dealers")
    return clean_data

if __name__ == "__main__":
    clean_dealer_list()