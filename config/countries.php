<?php

/*
|--------------------------------------------------------------------------
| Countries the store can deliver to overseas
|--------------------------------------------------------------------------
|
| Every country here was checked against Routely's quote API on 13 November
| 2026 and returned an express price from Lagos. Russia and Ukraine were not
| recognised by Routely, so they aren't listed. Store Settings chooses which
| of these are offered at checkout.
|
| - name: exactly as Routely knows it ("Ivory Coast", not "Côte d'Ivoire").
| - city / coordinates: where quotes are priced to before the customer types
|   an address. Routely prices by country and weight, not city.
| - postcode: a sample, or null where the country has no postcodes in
|   everyday use (checkout then doesn't ask for one; Routely gets "00000").
|
*/

return [
    // Europe
    'GB' => ['name' => 'United Kingdom', 'currency' => 'GBP', 'symbol' => '£', 'dial' => '+44', 'city' => 'London', 'coordinates' => '51.5072,-0.1276', 'postcode' => 'SW1A 1AA', 'postcode_label' => 'Postcode', 'region_label' => 'County', 'region_required' => false],
    'IE' => ['name' => 'Ireland', 'currency' => 'EUR', 'symbol' => '€', 'dial' => '+353', 'city' => 'Dublin', 'coordinates' => '53.3498,-6.2603', 'postcode' => 'D02 X285', 'postcode_label' => 'Eircode', 'region_label' => 'County', 'region_required' => false],
    'FR' => ['name' => 'France', 'currency' => 'EUR', 'symbol' => '€', 'dial' => '+33', 'city' => 'Paris', 'coordinates' => '48.8566,2.3522', 'postcode' => '75001', 'postcode_label' => 'Postal code', 'region_label' => 'Region', 'region_required' => false],
    'DE' => ['name' => 'Germany', 'currency' => 'EUR', 'symbol' => '€', 'dial' => '+49', 'city' => 'Berlin', 'coordinates' => '52.5200,13.4050', 'postcode' => '10115', 'postcode_label' => 'Postal code', 'region_label' => 'State', 'region_required' => false],
    'IT' => ['name' => 'Italy', 'currency' => 'EUR', 'symbol' => '€', 'dial' => '+39', 'city' => 'Rome', 'coordinates' => '41.9028,12.4964', 'postcode' => '00184', 'postcode_label' => 'Postal code', 'region_label' => 'Province', 'region_required' => false],
    'ES' => ['name' => 'Spain', 'currency' => 'EUR', 'symbol' => '€', 'dial' => '+34', 'city' => 'Madrid', 'coordinates' => '40.4168,-3.7038', 'postcode' => '28013', 'postcode_label' => 'Postal code', 'region_label' => 'Province', 'region_required' => false],
    'PT' => ['name' => 'Portugal', 'currency' => 'EUR', 'symbol' => '€', 'dial' => '+351', 'city' => 'Lisbon', 'coordinates' => '38.7223,-9.1393', 'postcode' => '1100-148', 'postcode_label' => 'Postal code', 'region_label' => 'District', 'region_required' => false],
    'NL' => ['name' => 'Netherlands', 'currency' => 'EUR', 'symbol' => '€', 'dial' => '+31', 'city' => 'Amsterdam', 'coordinates' => '52.3676,4.9041', 'postcode' => '1012 JS', 'postcode_label' => 'Postal code', 'region_label' => 'Province', 'region_required' => false],
    'BE' => ['name' => 'Belgium', 'currency' => 'EUR', 'symbol' => '€', 'dial' => '+32', 'city' => 'Brussels', 'coordinates' => '50.8503,4.3517', 'postcode' => '1000', 'postcode_label' => 'Postal code', 'region_label' => 'Province', 'region_required' => false],
    'LU' => ['name' => 'Luxembourg', 'currency' => 'EUR', 'symbol' => '€', 'dial' => '+352', 'city' => 'Luxembourg', 'coordinates' => '49.6116,6.1319', 'postcode' => '1009', 'postcode_label' => 'Postal code', 'region_label' => 'Canton', 'region_required' => false],
    'CH' => ['name' => 'Switzerland', 'currency' => 'CHF', 'symbol' => 'CHF ', 'dial' => '+41', 'city' => 'Zurich', 'coordinates' => '47.3769,8.5417', 'postcode' => '8001', 'postcode_label' => 'Postal code', 'region_label' => 'Canton', 'region_required' => false],
    'AT' => ['name' => 'Austria', 'currency' => 'EUR', 'symbol' => '€', 'dial' => '+43', 'city' => 'Vienna', 'coordinates' => '48.2082,16.3738', 'postcode' => '1010', 'postcode_label' => 'Postal code', 'region_label' => 'State', 'region_required' => false],
    'SE' => ['name' => 'Sweden', 'currency' => 'SEK', 'symbol' => 'kr ', 'dial' => '+46', 'city' => 'Stockholm', 'coordinates' => '59.3293,18.0686', 'postcode' => '111 22', 'postcode_label' => 'Postal code', 'region_label' => 'County', 'region_required' => false],
    'NO' => ['name' => 'Norway', 'currency' => 'NOK', 'symbol' => 'kr ', 'dial' => '+47', 'city' => 'Oslo', 'coordinates' => '59.9139,10.7522', 'postcode' => '0150', 'postcode_label' => 'Postal code', 'region_label' => 'County', 'region_required' => false],
    'DK' => ['name' => 'Denmark', 'currency' => 'DKK', 'symbol' => 'kr ', 'dial' => '+45', 'city' => 'Copenhagen', 'coordinates' => '55.6761,12.5683', 'postcode' => '1050', 'postcode_label' => 'Postal code', 'region_label' => 'Region', 'region_required' => false],
    'FI' => ['name' => 'Finland', 'currency' => 'EUR', 'symbol' => '€', 'dial' => '+358', 'city' => 'Helsinki', 'coordinates' => '60.1699,24.9384', 'postcode' => '00100', 'postcode_label' => 'Postal code', 'region_label' => 'Region', 'region_required' => false],
    'PL' => ['name' => 'Poland', 'currency' => 'PLN', 'symbol' => 'zł ', 'dial' => '+48', 'city' => 'Warsaw', 'coordinates' => '52.2297,21.0122', 'postcode' => '00-001', 'postcode_label' => 'Postal code', 'region_label' => 'Province', 'region_required' => false],
    'CZ' => ['name' => 'Czech Republic', 'currency' => 'CZK', 'symbol' => 'Kč ', 'dial' => '+420', 'city' => 'Prague', 'coordinates' => '50.0755,14.4378', 'postcode' => '110 00', 'postcode_label' => 'Postal code', 'region_label' => 'Region', 'region_required' => false],
    'GR' => ['name' => 'Greece', 'currency' => 'EUR', 'symbol' => '€', 'dial' => '+30', 'city' => 'Athens', 'coordinates' => '37.9838,23.7275', 'postcode' => '105 57', 'postcode_label' => 'Postal code', 'region_label' => 'Region', 'region_required' => false],
    'HU' => ['name' => 'Hungary', 'currency' => 'HUF', 'symbol' => 'Ft ', 'dial' => '+36', 'city' => 'Budapest', 'coordinates' => '47.4979,19.0402', 'postcode' => '1051', 'postcode_label' => 'Postal code', 'region_label' => 'County', 'region_required' => false],
    'RO' => ['name' => 'Romania', 'currency' => 'RON', 'symbol' => 'lei ', 'dial' => '+40', 'city' => 'Bucharest', 'coordinates' => '44.4268,26.1025', 'postcode' => '010011', 'postcode_label' => 'Postal code', 'region_label' => 'County', 'region_required' => false],
    'CY' => ['name' => 'Cyprus', 'currency' => 'EUR', 'symbol' => '€', 'dial' => '+357', 'city' => 'Nicosia', 'coordinates' => '35.1856,33.3823', 'postcode' => '1010', 'postcode_label' => 'Postal code', 'region_label' => 'District', 'region_required' => false],
    'MT' => ['name' => 'Malta', 'currency' => 'EUR', 'symbol' => '€', 'dial' => '+356', 'city' => 'Valletta', 'coordinates' => '35.8989,14.5146', 'postcode' => 'VLT 1117', 'postcode_label' => 'Postcode', 'region_label' => 'Region', 'region_required' => false],
    'TR' => ['name' => 'Turkey', 'currency' => 'TRY', 'symbol' => '₺', 'dial' => '+90', 'city' => 'Istanbul', 'coordinates' => '41.0082,28.9784', 'postcode' => '34110', 'postcode_label' => 'Postal code', 'region_label' => 'Province', 'region_required' => false],

    // North America, Latin America and the Caribbean
    'US' => ['name' => 'United States', 'currency' => 'USD', 'symbol' => '$', 'dial' => '+1', 'city' => 'New York', 'coordinates' => '40.7128,-74.0060', 'postcode' => '10001', 'postcode_label' => 'ZIP code', 'region_label' => 'State', 'region_required' => true],
    'CA' => ['name' => 'Canada', 'currency' => 'CAD', 'symbol' => 'C$', 'dial' => '+1', 'city' => 'Toronto', 'coordinates' => '43.6532,-79.3832', 'postcode' => 'M5V 3L9', 'postcode_label' => 'Postal code', 'region_label' => 'Province', 'region_required' => true],
    'MX' => ['name' => 'Mexico', 'currency' => 'MXN', 'symbol' => 'MX$', 'dial' => '+52', 'city' => 'Mexico City', 'coordinates' => '19.4326,-99.1332', 'postcode' => '06000', 'postcode_label' => 'Postal code', 'region_label' => 'State', 'region_required' => true],
    'BR' => ['name' => 'Brazil', 'currency' => 'BRL', 'symbol' => 'R$', 'dial' => '+55', 'city' => 'Sao Paulo', 'coordinates' => '-23.5505,-46.6333', 'postcode' => '01001-000', 'postcode_label' => 'CEP', 'region_label' => 'State', 'region_required' => true],
    'AR' => ['name' => 'Argentina', 'currency' => 'ARS', 'symbol' => 'AR$', 'dial' => '+54', 'city' => 'Buenos Aires', 'coordinates' => '-34.6037,-58.3816', 'postcode' => 'C1002', 'postcode_label' => 'Postal code', 'region_label' => 'Province', 'region_required' => true],
    'CL' => ['name' => 'Chile', 'currency' => 'CLP', 'symbol' => 'CLP$', 'dial' => '+56', 'city' => 'Santiago', 'coordinates' => '-33.4489,-70.6693', 'postcode' => '8320000', 'postcode_label' => 'Postal code', 'region_label' => 'Region', 'region_required' => false],
    'CO' => ['name' => 'Colombia', 'currency' => 'COP', 'symbol' => 'COL$', 'dial' => '+57', 'city' => 'Bogota', 'coordinates' => '4.7110,-74.0721', 'postcode' => '110111', 'postcode_label' => 'Postal code', 'region_label' => 'Department', 'region_required' => false],
    'JM' => ['name' => 'Jamaica', 'currency' => 'JMD', 'symbol' => 'J$', 'dial' => '+1', 'city' => 'Kingston', 'coordinates' => '18.0179,-76.8099', 'postcode' => null, 'postcode_label' => 'Postal code', 'region_label' => 'Parish', 'region_required' => false],
    'TT' => ['name' => 'Trinidad and Tobago', 'currency' => 'TTD', 'symbol' => 'TT$', 'dial' => '+1', 'city' => 'Port of Spain', 'coordinates' => '10.6549,-61.5019', 'postcode' => null, 'postcode_label' => 'Postal code', 'region_label' => 'Region', 'region_required' => false],
    'BB' => ['name' => 'Barbados', 'currency' => 'BBD', 'symbol' => 'Bds$', 'dial' => '+1', 'city' => 'Bridgetown', 'coordinates' => '13.0975,-59.6167', 'postcode' => null, 'postcode_label' => 'Postal code', 'region_label' => 'Parish', 'region_required' => false],

    // Middle East
    'AE' => ['name' => 'United Arab Emirates', 'currency' => 'AED', 'symbol' => 'AED ', 'dial' => '+971', 'city' => 'Dubai', 'coordinates' => '25.2048,55.2708', 'postcode' => null, 'postcode_label' => 'Postal code', 'region_label' => 'Emirate', 'region_required' => true],
    'SA' => ['name' => 'Saudi Arabia', 'currency' => 'SAR', 'symbol' => 'SAR ', 'dial' => '+966', 'city' => 'Riyadh', 'coordinates' => '24.7136,46.6753', 'postcode' => '11564', 'postcode_label' => 'Postal code', 'region_label' => 'Region', 'region_required' => false],
    'QA' => ['name' => 'Qatar', 'currency' => 'QAR', 'symbol' => 'QAR ', 'dial' => '+974', 'city' => 'Doha', 'coordinates' => '25.2854,51.5310', 'postcode' => null, 'postcode_label' => 'Postal code', 'region_label' => 'Municipality', 'region_required' => false],
    'KW' => ['name' => 'Kuwait', 'currency' => 'KWD', 'symbol' => 'KWD ', 'dial' => '+965', 'city' => 'Kuwait City', 'coordinates' => '29.3759,47.9774', 'postcode' => '13001', 'postcode_label' => 'Postal code', 'region_label' => 'Governorate', 'region_required' => false],
    'BH' => ['name' => 'Bahrain', 'currency' => 'BHD', 'symbol' => 'BHD ', 'dial' => '+973', 'city' => 'Manama', 'coordinates' => '26.2285,50.5860', 'postcode' => '317', 'postcode_label' => 'Postal code', 'region_label' => 'Governorate', 'region_required' => false],
    'OM' => ['name' => 'Oman', 'currency' => 'OMR', 'symbol' => 'OMR ', 'dial' => '+968', 'city' => 'Muscat', 'coordinates' => '23.5880,58.3829', 'postcode' => '100', 'postcode_label' => 'Postal code', 'region_label' => 'Governorate', 'region_required' => false],
    'IL' => ['name' => 'Israel', 'currency' => 'ILS', 'symbol' => '₪', 'dial' => '+972', 'city' => 'Tel Aviv', 'coordinates' => '32.0853,34.7818', 'postcode' => '6100000', 'postcode_label' => 'Postal code', 'region_label' => 'District', 'region_required' => false],
    'JO' => ['name' => 'Jordan', 'currency' => 'JOD', 'symbol' => 'JOD ', 'dial' => '+962', 'city' => 'Amman', 'coordinates' => '31.9454,35.9284', 'postcode' => '11118', 'postcode_label' => 'Postal code', 'region_label' => 'Governorate', 'region_required' => false],

    // Africa
    'EG' => ['name' => 'Egypt', 'currency' => 'EGP', 'symbol' => 'E£', 'dial' => '+20', 'city' => 'Cairo', 'coordinates' => '30.0444,31.2357', 'postcode' => '11511', 'postcode_label' => 'Postal code', 'region_label' => 'Governorate', 'region_required' => false],
    'MA' => ['name' => 'Morocco', 'currency' => 'MAD', 'symbol' => 'MAD ', 'dial' => '+212', 'city' => 'Casablanca', 'coordinates' => '33.5731,-7.5898', 'postcode' => '20000', 'postcode_label' => 'Postal code', 'region_label' => 'Region', 'region_required' => false],
    'GH' => ['name' => 'Ghana', 'currency' => 'GHS', 'symbol' => 'GH₵', 'dial' => '+233', 'city' => 'Accra', 'coordinates' => '5.6037,-0.1870', 'postcode' => null, 'postcode_label' => 'Postal code', 'region_label' => 'Region', 'region_required' => false],
    'ZA' => ['name' => 'South Africa', 'currency' => 'ZAR', 'symbol' => 'R', 'dial' => '+27', 'city' => 'Johannesburg', 'coordinates' => '-26.2041,28.0473', 'postcode' => '2000', 'postcode_label' => 'Postal code', 'region_label' => 'Province', 'region_required' => true],
    'KE' => ['name' => 'Kenya', 'currency' => 'KES', 'symbol' => 'KSh ', 'dial' => '+254', 'city' => 'Nairobi', 'coordinates' => '-1.2921,36.8219', 'postcode' => '00100', 'postcode_label' => 'Postal code', 'region_label' => 'County', 'region_required' => false],
    'CI' => ['name' => 'Ivory Coast', 'currency' => 'XOF', 'symbol' => 'CFA ', 'dial' => '+225', 'city' => 'Abidjan', 'coordinates' => '5.3600,-4.0083', 'postcode' => null, 'postcode_label' => 'Postal code', 'region_label' => 'District', 'region_required' => false],
    'SN' => ['name' => 'Senegal', 'currency' => 'XOF', 'symbol' => 'CFA ', 'dial' => '+221', 'city' => 'Dakar', 'coordinates' => '14.7167,-17.4677', 'postcode' => '10200', 'postcode_label' => 'Postal code', 'region_label' => 'Region', 'region_required' => false],
    'CM' => ['name' => 'Cameroon', 'currency' => 'XAF', 'symbol' => 'FCFA ', 'dial' => '+237', 'city' => 'Douala', 'coordinates' => '4.0511,9.7679', 'postcode' => null, 'postcode_label' => 'Postal code', 'region_label' => 'Region', 'region_required' => false],
    'BJ' => ['name' => 'Benin', 'currency' => 'XOF', 'symbol' => 'CFA ', 'dial' => '+229', 'city' => 'Cotonou', 'coordinates' => '6.3703,2.3912', 'postcode' => null, 'postcode_label' => 'Postal code', 'region_label' => 'Department', 'region_required' => false],
    'TG' => ['name' => 'Togo', 'currency' => 'XOF', 'symbol' => 'CFA ', 'dial' => '+228', 'city' => 'Lome', 'coordinates' => '6.1256,1.2254', 'postcode' => null, 'postcode_label' => 'Postal code', 'region_label' => 'Region', 'region_required' => false],
    'RW' => ['name' => 'Rwanda', 'currency' => 'RWF', 'symbol' => 'RF ', 'dial' => '+250', 'city' => 'Kigali', 'coordinates' => '-1.9441,30.0619', 'postcode' => null, 'postcode_label' => 'Postal code', 'region_label' => 'Province', 'region_required' => false],
    'UG' => ['name' => 'Uganda', 'currency' => 'UGX', 'symbol' => 'USh ', 'dial' => '+256', 'city' => 'Kampala', 'coordinates' => '0.3476,32.5825', 'postcode' => null, 'postcode_label' => 'Postal code', 'region_label' => 'District', 'region_required' => false],
    'TZ' => ['name' => 'Tanzania', 'currency' => 'TZS', 'symbol' => 'TSh ', 'dial' => '+255', 'city' => 'Dar es Salaam', 'coordinates' => '-6.7924,39.2083', 'postcode' => '11101', 'postcode_label' => 'Postal code', 'region_label' => 'Region', 'region_required' => false],
    'ET' => ['name' => 'Ethiopia', 'currency' => 'ETB', 'symbol' => 'Br ', 'dial' => '+251', 'city' => 'Addis Ababa', 'coordinates' => '9.0300,38.7400', 'postcode' => '1000', 'postcode_label' => 'Postal code', 'region_label' => 'Region', 'region_required' => false],
    'BW' => ['name' => 'Botswana', 'currency' => 'BWP', 'symbol' => 'P ', 'dial' => '+267', 'city' => 'Gaborone', 'coordinates' => '-24.6282,25.9231', 'postcode' => null, 'postcode_label' => 'Postal code', 'region_label' => 'District', 'region_required' => false],
    'ZM' => ['name' => 'Zambia', 'currency' => 'ZMW', 'symbol' => 'K ', 'dial' => '+260', 'city' => 'Lusaka', 'coordinates' => '-15.3875,28.3228', 'postcode' => '10101', 'postcode_label' => 'Postal code', 'region_label' => 'Province', 'region_required' => false],
    'ZW' => ['name' => 'Zimbabwe', 'currency' => 'USD', 'symbol' => '$', 'dial' => '+263', 'city' => 'Harare', 'coordinates' => '-17.8252,31.0335', 'postcode' => null, 'postcode_label' => 'Postal code', 'region_label' => 'Province', 'region_required' => false],
    'GM' => ['name' => 'Gambia', 'currency' => 'GMD', 'symbol' => 'D ', 'dial' => '+220', 'city' => 'Banjul', 'coordinates' => '13.4549,-16.5790', 'postcode' => null, 'postcode_label' => 'Postal code', 'region_label' => 'Region', 'region_required' => false],
    'SL' => ['name' => 'Sierra Leone', 'currency' => 'SLE', 'symbol' => 'Le ', 'dial' => '+232', 'city' => 'Freetown', 'coordinates' => '8.4657,-13.2317', 'postcode' => null, 'postcode_label' => 'Postal code', 'region_label' => 'Province', 'region_required' => false],
    'LR' => ['name' => 'Liberia', 'currency' => 'LRD', 'symbol' => 'L$', 'dial' => '+231', 'city' => 'Monrovia', 'coordinates' => '6.3156,-10.8074', 'postcode' => '1000', 'postcode_label' => 'Postal code', 'region_label' => 'County', 'region_required' => false],

    // Asia and Oceania
    'CN' => ['name' => 'China', 'currency' => 'CNY', 'symbol' => 'CN¥', 'dial' => '+86', 'city' => 'Shanghai', 'coordinates' => '31.2304,121.4737', 'postcode' => '200000', 'postcode_label' => 'Postal code', 'region_label' => 'Province', 'region_required' => true],
    'HK' => ['name' => 'Hong Kong', 'currency' => 'HKD', 'symbol' => 'HK$', 'dial' => '+852', 'city' => 'Hong Kong', 'coordinates' => '22.3193,114.1694', 'postcode' => null, 'postcode_label' => 'Postal code', 'region_label' => 'District', 'region_required' => false],
    'JP' => ['name' => 'Japan', 'currency' => 'JPY', 'symbol' => '¥', 'dial' => '+81', 'city' => 'Tokyo', 'coordinates' => '35.6762,139.6503', 'postcode' => '100-0001', 'postcode_label' => 'Postal code', 'region_label' => 'Prefecture', 'region_required' => true],
    'KR' => ['name' => 'South Korea', 'currency' => 'KRW', 'symbol' => '₩', 'dial' => '+82', 'city' => 'Seoul', 'coordinates' => '37.5665,126.9780', 'postcode' => '04524', 'postcode_label' => 'Postal code', 'region_label' => 'Province', 'region_required' => false],
    'SG' => ['name' => 'Singapore', 'currency' => 'SGD', 'symbol' => 'S$', 'dial' => '+65', 'city' => 'Singapore', 'coordinates' => '1.3521,103.8198', 'postcode' => '018956', 'postcode_label' => 'Postal code', 'region_label' => 'District', 'region_required' => false],
    'MY' => ['name' => 'Malaysia', 'currency' => 'MYR', 'symbol' => 'RM ', 'dial' => '+60', 'city' => 'Kuala Lumpur', 'coordinates' => '3.1390,101.6869', 'postcode' => '50000', 'postcode_label' => 'Postcode', 'region_label' => 'State', 'region_required' => true],
    'IN' => ['name' => 'India', 'currency' => 'INR', 'symbol' => '₹', 'dial' => '+91', 'city' => 'Mumbai', 'coordinates' => '19.0760,72.8777', 'postcode' => '400001', 'postcode_label' => 'PIN code', 'region_label' => 'State', 'region_required' => true],
    'PK' => ['name' => 'Pakistan', 'currency' => 'PKR', 'symbol' => 'Rs ', 'dial' => '+92', 'city' => 'Karachi', 'coordinates' => '24.8607,67.0011', 'postcode' => '74000', 'postcode_label' => 'Postal code', 'region_label' => 'Province', 'region_required' => false],
    'PH' => ['name' => 'Philippines', 'currency' => 'PHP', 'symbol' => '₱', 'dial' => '+63', 'city' => 'Manila', 'coordinates' => '14.5995,120.9842', 'postcode' => '1000', 'postcode_label' => 'ZIP code', 'region_label' => 'Province', 'region_required' => false],
    'ID' => ['name' => 'Indonesia', 'currency' => 'IDR', 'symbol' => 'Rp ', 'dial' => '+62', 'city' => 'Jakarta', 'coordinates' => '-6.2088,106.8456', 'postcode' => '10110', 'postcode_label' => 'Postal code', 'region_label' => 'Province', 'region_required' => false],
    'TH' => ['name' => 'Thailand', 'currency' => 'THB', 'symbol' => '฿', 'dial' => '+66', 'city' => 'Bangkok', 'coordinates' => '13.7563,100.5018', 'postcode' => '10200', 'postcode_label' => 'Postal code', 'region_label' => 'Province', 'region_required' => false],
    'VN' => ['name' => 'Vietnam', 'currency' => 'VND', 'symbol' => '₫', 'dial' => '+84', 'city' => 'Ho Chi Minh City', 'coordinates' => '10.8231,106.6297', 'postcode' => '700000', 'postcode_label' => 'Postal code', 'region_label' => 'Province', 'region_required' => false],
    'AU' => ['name' => 'Australia', 'currency' => 'AUD', 'symbol' => 'A$', 'dial' => '+61', 'city' => 'Sydney', 'coordinates' => '-33.8688,151.2093', 'postcode' => '2000', 'postcode_label' => 'Postcode', 'region_label' => 'State', 'region_required' => true],
    'NZ' => ['name' => 'New Zealand', 'currency' => 'NZD', 'symbol' => 'NZ$', 'dial' => '+64', 'city' => 'Auckland', 'coordinates' => '-36.8485,174.7633', 'postcode' => '1010', 'postcode_label' => 'Postcode', 'region_label' => 'Region', 'region_required' => false],
];
