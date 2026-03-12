<?php

namespace App\Services;

class LocationService
{
    /**
     * Returns all countries as [id => name].
     */
    public static function countries(): array
    {
        return [
            1   => 'Afghanistan', 2   => 'Albania', 3   => 'Algeria',
            4   => 'American Samoa', 5   => 'Andorra', 6   => 'Angola',
            7   => 'Anguilla', 8   => 'Antarctica', 9   => 'Antigua And Barbuda',
            10  => 'Argentina', 11  => 'Armenia', 12  => 'Aruba',
            13  => 'Australia', 14  => 'Austria', 15  => 'Azerbaijan',
            16  => 'Bahamas The', 17  => 'Bahrain', 18  => 'Bangladesh',
            19  => 'Barbados', 20  => 'Belarus', 21  => 'Belgium',
            22  => 'Belize', 23  => 'Benin', 24  => 'Bermuda',
            25  => 'Bhutan', 26  => 'Bolivia', 27  => 'Bosnia and Herzegovina',
            28  => 'Botswana', 29  => 'Bouvet Island', 30  => 'Brazil',
            31  => 'British Indian Ocean Territory', 32  => 'Brunei', 33  => 'Bulgaria',
            34  => 'Burkina Faso', 35  => 'Burundi', 36  => 'Cambodia',
            37  => 'Cameroon', 38  => 'Canada', 39  => 'Cape Verde',
            40  => 'Cayman Islands', 41  => 'Central African Republic', 42  => 'Chad',
            43  => 'Chile', 44  => 'China', 45  => 'Christmas Island',
            46  => 'Cocos (Keeling) Islands', 47  => 'Colombia', 48  => 'Comoros',
            49  => 'Congo', 50  => 'Congo The Democratic Republic Of The',
            51  => 'Cook Islands', 52  => 'Costa Rica', 53  => "Cote D'Ivoire (Ivory Coast)",
            54  => 'Croatia (Hrvatska)', 55  => 'Cuba', 56  => 'Cyprus',
            57  => 'Czech Republic', 58  => 'Denmark', 59  => 'Djibouti',
            60  => 'Dominica', 61  => 'Dominican Republic', 62  => 'East Timor',
            63  => 'Ecuador', 64  => 'Egypt', 65  => 'El Salvador',
            66  => 'Equatorial Guinea', 67  => 'Eritrea', 68  => 'Estonia',
            69  => 'Ethiopia', 70  => 'External Territories of Australia',
            71  => 'Falkland Islands', 72  => 'Faroe Islands', 73  => 'Fiji Islands',
            74  => 'Finland', 75  => 'France', 76  => 'French Guiana',
            77  => 'French Polynesia', 78  => 'French Southern Territories',
            79  => 'Gabon', 80  => 'Gambia The', 81  => 'Georgia',
            82  => 'Germany', 83  => 'Ghana', 84  => 'Gibraltar',
            85  => 'Greece', 86  => 'Greenland', 87  => 'Grenada',
            88  => 'Guadeloupe', 89  => 'Guam', 90  => 'Guatemala',
            91  => 'Guernsey and Alderney', 92  => 'Guinea', 93  => 'Guinea-Bissau',
            94  => 'Guyana', 95  => 'Haiti', 96  => 'Heard and McDonald Islands',
            97  => 'Honduras', 98  => 'Hong Kong S.A.R.', 99  => 'Hungary',
            100 => 'Iceland', 101 => 'India', 102 => 'Indonesia',
            103 => 'Iran', 104 => 'Iraq', 105 => 'Ireland',
            106 => 'Israel', 107 => 'Italy', 108 => 'Jamaica',
            109 => 'Japan', 110 => 'Jersey', 111 => 'Jordan',
            112 => 'Kazakhstan', 113 => 'Kenya', 114 => 'Kiribati',
            115 => 'Korea North', 116 => 'Korea South', 117 => 'Kuwait',
            118 => 'Kyrgyzstan', 119 => 'Laos', 120 => 'Latvia',
            121 => 'Lebanon', 122 => 'Lesotho', 123 => 'Liberia',
            124 => 'Libya', 125 => 'Liechtenstein', 126 => 'Lithuania',
            127 => 'Luxembourg', 128 => 'Macau S.A.R.', 129 => 'Macedonia',
            130 => 'Madagascar', 131 => 'Malawi', 132 => 'Malaysia',
            133 => 'Maldives', 134 => 'Mali', 135 => 'Malta',
            136 => 'Man (Isle of)', 137 => 'Marshall Islands', 138 => 'Martinique',
            139 => 'Mauritania', 140 => 'Mauritius', 141 => 'Mayotte',
            142 => 'Mexico', 143 => 'Micronesia', 144 => 'Moldova',
            145 => 'Monaco', 146 => 'Mongolia', 147 => 'Montserrat',
            148 => 'Morocco', 149 => 'Mozambique', 150 => 'Myanmar',
            151 => 'Namibia', 152 => 'Nauru', 153 => 'Nepal',
            154 => 'Netherlands Antilles', 155 => 'Netherlands The', 156 => 'New Caledonia',
            157 => 'New Zealand', 158 => 'Nicaragua', 159 => 'Niger',
            160 => 'Nigeria', 161 => 'Niue', 162 => 'Norfolk Island',
            163 => 'Northern Mariana Islands', 164 => 'Norway', 165 => 'Oman',
            166 => 'Pakistan', 167 => 'Palau', 168 => 'Palestinian Territory Occupied',
            169 => 'Panama', 170 => 'Papua new Guinea', 171 => 'Paraguay',
            172 => 'Peru', 173 => 'Philippines', 174 => 'Pitcairn Island',
            175 => 'Poland', 176 => 'Portugal', 177 => 'Puerto Rico',
            178 => 'Qatar', 179 => 'Reunion', 180 => 'Romania',
            181 => 'Russia', 182 => 'Rwanda', 183 => 'Saint Helena',
            184 => 'Saint Kitts And Nevis', 185 => 'Saint Lucia',
            186 => 'Saint Pierre and Miquelon', 187 => 'Saint Vincent And The Grenadines',
            188 => 'Samoa', 189 => 'San Marino', 190 => 'Sao Tome and Principe',
            191 => 'Saudi Arabia', 192 => 'Senegal', 193 => 'Serbia',
            194 => 'Seychelles', 195 => 'Sierra Leone', 196 => 'Singapore',
            197 => 'Slovakia', 198 => 'Slovenia', 199 => 'Smaller Territories of the UK',
            200 => 'Solomon Islands', 201 => 'Somalia', 202 => 'South Africa',
            203 => 'South Georgia', 204 => 'South Sudan', 205 => 'Spain',
            206 => 'Sri Lanka', 207 => 'Sudan', 208 => 'Suriname',
            209 => 'Svalbard And Jan Mayen Islands', 210 => 'Swaziland',
            211 => 'Sweden', 212 => 'Switzerland', 213 => 'Syria',
            214 => 'Taiwan', 215 => 'Tajikistan', 216 => 'Tanzania',
            217 => 'Thailand', 218 => 'Togo', 219 => 'Tokelau',
            220 => 'Tonga', 221 => 'Trinidad And Tobago', 222 => 'Tunisia',
            223 => 'Turkey', 224 => 'Turkmenistan', 225 => 'Turks And Caicos Islands',
            226 => 'Tuvalu', 227 => 'Uganda', 228 => 'Ukraine',
            229 => 'United Arab Emirates', 230 => 'United Kingdom', 231 => 'United States',
            232 => 'United States Minor Outlying Islands', 233 => 'Uruguay',
            234 => 'Uzbekistan', 235 => 'Vanuatu', 236 => 'Vatican City State (Holy See)',
            237 => 'Venezuela', 238 => 'Vietnam', 239 => 'Virgin Islands (British)',
            240 => 'Virgin Islands (US)', 241 => 'Wallis And Futuna Islands',
            242 => 'Western Sahara', 243 => 'Yemen', 244 => 'Yugoslavia',
            245 => 'Zambia', 246 => 'Zimbabwe',
        ];
    }

    /**
     * Returns Indian states as [id => name].
     * IDs match those used in the original legacy system.
     */
    public static function indiaStates(): array
    {
        return [
            1  => 'Andaman and Nicobar Islands',
            2  => 'Andhra Pradesh',
            3  => 'Arunachal Pradesh',
            4  => 'Assam',
            5  => 'Bihar',
            6  => 'Chandigarh',
            7  => 'Chhattisgarh',
            8  => 'Dadra and Nagar Haveli',
            9  => 'Daman and Diu',
            10 => 'Delhi',
            11 => 'Goa',
            12 => 'Gujarat',
            13 => 'Haryana',
            14 => 'Himachal Pradesh',
            15 => 'Jammu and Kashmir',
            16 => 'Jharkhand',
            17 => 'Karnataka',
            18 => 'Kenmore',
            19 => 'Kerala',
            20 => 'Lakshadweep',
            21 => 'Madhya Pradesh',
            22 => 'Maharashtra',
            23 => 'Manipur',
            24 => 'Meghalaya',
            25 => 'Mizoram',
            26 => 'Nagaland',
            27 => 'Narora',
            28 => 'Natwar',
            29 => 'Odisha',
            30 => 'Paschim Medinipur',
            31 => 'Pondicherry',
            32 => 'Punjab',
            33 => 'Rajasthan',
            34 => 'Sikkim',
            35 => 'Tamil Nadu',
            36 => 'Telangana',
            37 => 'Tripura',
            38 => 'Uttar Pradesh',
            39 => 'Uttarakhand',
            40 => 'Vaishali',
            41 => 'West Bengal',
        ];
    }

    /**
     * Returns states for a given country id.
     * Only India (101) is fully supported.
     */
    public static function statesByCountry(int $countryId): array
    {
        if ($countryId === 101) {
            return self::indiaStates();
        }
        return [];
    }

    /**
     * Returns cities [id => name] for a given state id.
     */
    public static function citiesByState(int $stateId): array
    {
        $cities = [
            1 => [
                10001 => 'Port Blair', 10002 => 'Diglipur', 10003 => 'Mayabunder', 10004 => 'Rangat', 10005 => 'Havelock Island',
            ],

            // Andhra Pradesh (2)
            2 => [
                1 => 'Hyderabad', 2 => 'Visakhapatnam', 3 => 'Vijayawada',
                4 => 'Guntur', 5 => 'Nellore', 6 => 'Kurnool',
                7 => 'Rajamahendravaram', 8 => 'Kadapa', 9 => 'Kakinada',
                10 => 'Tirupati',
            ],

            3 => [
                10021 => 'Itanagar', 10022 => 'Naharlagun', 10023 => 'Tawang', 10024 => 'Pasighat', 10025 => 'Ziro',
            ],

            // Assam (4)
            4 => [
                1100 => 'Guwahati', 1101 => 'Silchar', 1102 => 'Dibrugarh',
                1103 => 'Jorhat', 1104 => 'Nagaon', 1105 => 'Tinsukia',
                1106 => 'Tezpur', 1107 => 'Bongaigaon', 1108 => 'Dhubri',
                1109 => 'Diphu',
            ],

            // Bihar (5)
            5 => [
                800 => 'Patna', 801 => 'Gaya', 802 => 'Bhagalpur',
                803 => 'Muzaffarpur', 804 => 'Darbhanga', 805 => 'Bihar Sharif',
                806 => 'Arrah', 807 => 'Begusarai', 808 => 'Katihar',
                809 => 'Purnia',
            ],

            6 => [
                10031 => 'Chandigarh', 10032 => 'Manimajra', 10033 => 'Sector 17', 10034 => 'Sector 22', 10035 => 'Industrial Area',
            ],

            7 => [
                10041 => 'Raipur', 10042 => 'Bhilai', 10043 => 'Bilaspur', 10044 => 'Korba', 10045 => 'Durg',
            ],

            8 => [
                10051 => 'Silvassa', 10052 => 'Amli', 10053 => 'Naroli', 10054 => 'Samarvarni', 10055 => 'Rakholi',
            ],

            9 => [
                10061 => 'Daman', 10062 => 'Diu', 10063 => 'Nani Daman', 10064 => 'Moti Daman', 10065 => 'Vanakbara',
            ],

            // Delhi (10)
            10 => [
                100 => 'New Delhi', 101 => 'Old Delhi', 102 => 'Dwarka',
                103 => 'Rohini', 104 => 'Saket', 105 => 'Lajpat Nagar',
                106 => 'Connaught Place', 107 => 'Janakpuri', 108 => 'Karol Bagh',
                109 => 'Pitampura',
            ],

            11 => [
                10071 => 'Panaji', 10072 => 'Margao', 10073 => 'Vasco da Gama', 10074 => 'Mapusa', 10075 => 'Ponda',
            ],

            // Gujarat (12)
            12 => [
                500 => 'Ahmedabad', 501 => 'Surat', 502 => 'Vadodara',
                503 => 'Rajkot', 504 => 'Bhavnagar', 505 => 'Jamnagar',
                506 => 'Junagadh', 507 => 'Gandhinagar', 508 => 'Anand',
                509 => 'Nadiad',
            ],

            // Haryana (13)
            13 => [
                1500 => 'Faridabad', 1501 => 'Gurgaon', 1502 => 'Panipat',
                1503 => 'Ambala', 1504 => 'Yamunanagar', 1505 => 'Rohtak',
                1506 => 'Hisar', 1507 => 'Karnal', 1508 => 'Sonipat',
                1509 => 'Panchkula',
            ],

            14 => [
                10081 => 'Shimla', 10082 => 'Dharamshala', 10083 => 'Solan', 10084 => 'Mandi', 10085 => 'Kullu',
            ],

            15 => [
                10091 => 'Srinagar', 10092 => 'Jammu', 10093 => 'Anantnag', 10094 => 'Baramulla', 10095 => 'Udhampur',
            ],

            // Jharkhand (16)
            16 => [
                1300 => 'Ranchi', 1301 => 'Jamshedpur', 1302 => 'Dhanbad',
                1303 => 'Bokaro', 1304 => 'Hazaribagh', 1305 => 'Deoghar',
                1306 => 'Giridih', 1307 => 'Ramgarh', 1308 => 'Phusro',
                1309 => 'Medininagar',
            ],

            // Karnataka (17)
            17 => [
                300 => 'Bengaluru', 301 => 'Mysuru', 302 => 'Hubli',
                303 => 'Mangaluru', 304 => 'Belagavi', 305 => 'Davanagere',
                306 => 'Ballari', 307 => 'Tumakuru', 308 => 'Vijayapura',
                309 => 'Shivamogga',
            ],

            18 => [
                10101 => 'Kenmore', 10102 => 'Kenmore East', 10103 => 'Kenmore West', 10104 => 'Kenmore Central', 10105 => 'Kenmore Town',
            ],

            // Kerala (19)
            19 => [
                900 => 'Thiruvananthapuram', 901 => 'Kochi', 902 => 'Kozhikode',
                903 => 'Thrissur', 904 => 'Kollam', 905 => 'Palakkad',
                906 => 'Alappuzha', 907 => 'Kannur', 908 => 'Kottayam',
                909 => 'Malappuram',
            ],

            20 => [
                10111 => 'Kavaratti', 10112 => 'Agatti', 10113 => 'Amini', 10114 => 'Andrott', 10115 => 'Minicoy',
            ],

            // Madhya Pradesh (21)
            21 => [
                1400 => 'Bhopal', 1401 => 'Indore', 1402 => 'Jabalpur',
                1403 => 'Gwalior', 1404 => 'Ujjain', 1405 => 'Sagar',
                1406 => 'Dewas', 1407 => 'Satna', 1408 => 'Ratlam',
                1409 => 'Rewa',
            ],

            // Maharashtra (22)
            22 => [
                200 => 'Mumbai', 201 => 'Pune', 202 => 'Nagpur',
                203 => 'Thane', 204 => 'Nashik', 205 => 'Aurangabad',
                206 => 'Solapur', 207 => 'Kolhapur', 208 => 'Amravati',
                209 => 'Navi Mumbai',
            ],

            23 => [
                10121 => 'Imphal', 10122 => 'Thoubal', 10123 => 'Bishnupur', 10124 => 'Churachandpur', 10125 => 'Ukhrul',
            ],

            24 => [
                10131 => 'Shillong', 10132 => 'Tura', 10133 => 'Jowai', 10134 => 'Nongstoin', 10135 => 'Baghmara',
            ],

            25 => [
                10141 => 'Aizawl', 10142 => 'Lunglei', 10143 => 'Champhai', 10144 => 'Serchhip', 10145 => 'Kolasib',
            ],

            26 => [
                10151 => 'Kohima', 10152 => 'Dimapur', 10153 => 'Mokokchung', 10154 => 'Tuensang', 10155 => 'Wokha',
            ],

            27 => [
                10161 => 'Narora', 10162 => 'Narora Township', 10163 => 'Narora Colony', 10164 => 'Narora Bazar', 10165 => 'Narora Road',
            ],

            28 => [
                10171 => 'Natwar', 10172 => 'Natwar Bazar', 10173 => 'Natwar Road', 10174 => 'Natwar Colony', 10175 => 'Natwar Junction',
            ],

            // Odisha (29)
            29 => [
                1200 => 'Bhubaneswar', 1201 => 'Cuttack', 1202 => 'Rourkela',
                1203 => 'Brahmapur', 1204 => 'Sambalpur', 1205 => 'Puri',
                1206 => 'Balasore', 1207 => 'Bhadrak', 1208 => 'Baripada',
                1209 => 'Jharsuguda',
            ],

            // West Bengal (41)
            41 => [
                5312 => '24 Parganas (N)', 5313 => '24 Parganas (S)', 5314 => 'Adra',
                5315 => 'Ahmadpur', 5316 => 'Aiho', 5317 => 'Aistala',
                5318 => 'Alipur Duar', 5319 => 'Alipur Duar Railway Junction', 5320 => 'Alpur',
                5321 => 'Amalhara', 5322 => 'Amkula', 5323 => 'Amlagora',
                5324 => 'Amodghata', 5325 => 'Amtala', 5326 => 'Andul',
                5327 => 'Anksa', 5328 => 'Ankurhati', 5329 => 'Anup Nagar',
                5330 => 'Arambagh', 5331 => 'Argari', 5332 => 'Arsha',
                5333 => 'Asansol', 5334 => 'Ashoknagar Kalyangarh', 5335 => 'Aurangabad',
                5336 => 'Bablari Dewanganj', 5337 => 'Badhagachhi', 5338 => 'Baduria',
                5339 => 'Baghdogra', 5340 => 'Bagnan', 5341 => 'Bagra',
                5342 => 'Bagula', 5343 => 'Baharampur', 5344 => 'Bahirgram',
                5345 => 'Bahula', 5346 => 'Baidyabati', 5347 => 'Bairatisal',
                5348 => 'Baj Baj', 5349 => 'Bakreswar', 5350 => 'Balaram Pota',
                5351 => 'Balarampur', 5352 => 'Bali Chak', 5353 => 'Ballavpur',
                5354 => 'Bally', 5355 => 'Balurghat', 5356 => 'Bamunari',
                5357 => 'Banarhat Tea Garden', 5358 => 'Bandel', 5359 => 'Bangaon',
                5360 => 'Bankra', 5361 => 'Bankura', 5362 => 'Bansbaria',
                5363 => 'Banshra', 5364 => 'Banupur', 5365 => 'Bara Bamonia',
                5366 => 'Barakpur', 5367 => 'Barakpur Cantonment', 5368 => 'Baranagar',
                5369 => 'Barasat', 5370 => 'Barddhaman', 5371 => 'Barijhati',
                5372 => 'Barjora', 5373 => 'Barrackpore', 5374 => 'Baruihuda',
                5375 => 'Baruipur', 5376 => 'Barunda', 5377 => 'Basirhat',
                5378 => 'Baska', 5379 => 'Begampur', 5380 => 'Beldanga',
                5381 => 'Beldubi', 5382 => 'Belebathan', 5383 => 'Beliator',
                5384 => 'Bhadreswar', 5385 => 'Bhandardaha', 5386 => 'Bhangar Raghunathpur',
                5387 => 'Bhangri Pratham Khanda', 5388 => 'Bhanowara', 5389 => 'Bhatpara',
                5390 => 'Bholar Dabri', 5391 => 'Bidhannagar', 5392 => 'Bidyadharpur',
                5393 => 'Biki Hakola', 5394 => 'Bilandapur', 5395 => 'Bilpahari',
                5396 => 'Bipra Noapara', 5397 => 'Birlapur', 5398 => 'Birnagar',
                5399 => 'Bisarpara', 5400 => 'Bishnupur', 5401 => 'Bolpur',
                5402 => 'Bongaon', 5403 => 'Bowali', 5404 => 'Burdwan',
                5405 => 'Canning', 5406 => 'Cart Road', 5407 => 'Chachanda',
                5408 => 'Chak Bankola', 5409 => 'Chak Enayetnagar', 5410 => 'Chak Kashipur',
                5411 => 'Chakalampur', 5412 => 'Chakbansberia', 5413 => 'Chakdaha',
                5414 => 'Chakpara', 5415 => 'Champahati', 5416 => 'Champdani',
                5417 => 'Chamrail', 5418 => 'Chandannagar', 5419 => 'Chandpur',
                5420 => 'Chandrakona', 5421 => 'Chapari', 5422 => 'Chapui',
                5423 => 'Char Brahmanagar', 5424 => 'Char Maijdia', 5425 => 'Charka',
                5426 => 'Chata Kalikapur', 5427 => 'Chauhati', 5428 => 'Checha Khata',
                5429 => 'Chelad', 5430 => 'Chhora', 5431 => 'Chikrand',
                5432 => 'Chittaranjan', 5433 => 'Contai', 5434 => 'Cooch Behar',
                5435 => 'Dainhat', 5436 => 'Dakshin Baguan', 5437 => 'Dakshin Jhapardaha',
                5438 => 'Dakshin Rajyadharpur', 5439 => 'Dakshin Raypur', 5440 => 'Dalkola',
                5441 => 'Dalurband', 5442 => 'Darap Pur', 5443 => 'Darjiling',
                5444 => 'Daulatpur', 5445 => 'Debipur', 5446 => 'Defahat',
                5447 => 'Deora', 5448 => 'Deulia', 5449 => 'Dhakuria',
                5450 => 'Dhandadihi', 5451 => 'Dhanyakuria', 5452 => 'Dharmapur',
                5453 => 'Dhatri Gram', 5454 => 'Dhuilya', 5455 => 'Dhulagari',
                5456 => 'Dhulian', 5457 => 'Dhupgari', 5458 => 'Dhusaripara',
                5459 => 'Diamond Harbour', 5460 => 'Digha', 5461 => 'Dignala',
                5462 => 'Dinhata', 5463 => 'Dubrajpur', 5464 => 'Dumjor',
                5465 => 'Durgapur', 5466 => 'Durllabhganj', 5467 => 'Egra',
                5468 => 'Eksara', 5469 => 'Falakata', 5470 => 'Farakka',
                5471 => 'Fatellapur', 5472 => 'Fort Gloster', 5473 => 'Gabberia',
                5474 => 'Gadigachha', 5475 => 'Gairkata', 5476 => 'Gangarampur',
                5477 => 'Garalgachha', 5478 => 'Garbeta Amlagora', 5479 => 'Garhbeta',
                5480 => 'Garshyamnagar', 5481 => 'Garui', 5482 => 'Garulia',
                5483 => 'Gayespur', 5484 => 'Ghatal', 5485 => 'Ghorsala',
                5486 => 'Goaljan', 5487 => 'Goasafat', 5488 => 'Gobardanga',
                5489 => 'Gobindapur', 5490 => 'Gopalpur', 5491 => 'Gopinathpur',
                5492 => 'Gora Bazar', 5493 => 'Guma', 5494 => 'Gurdaha',
                5495 => 'Guriahati', 5496 => 'Guskhara', 5497 => 'Habra',
                5498 => 'Haldia', 5499 => 'Haldibari', 5500 => 'Halisahar',
                5501 => 'Haora', 5502 => 'Harharia Chak', 5503 => 'Harindanga',
                5504 => 'Haringhata', 5505 => 'Haripur', 5506 => 'Harishpur',
                5507 => 'Hatgachha', 5508 => 'Hatsimla', 5509 => 'Hijuli',
                5510 => 'Hindustan Cables Town', 5511 => 'Hooghly', 5512 => 'Howrah',
                5513 => 'Hugli-Chunchura', 5514 => 'Humaipur', 5515 => 'Ichha Pur Defence Estate',
                5516 => 'Ingraj Bazar', 5517 => 'Islampur', 5518 => 'Jafarpur',
                5519 => 'Jagadanandapur', 5520 => 'Jagdishpur', 5521 => 'Jagtaj',
                5522 => 'Jala Kendua', 5523 => 'Jaldhaka', 5524 => 'Jalkhura',
                5525 => 'Jalpaiguri', 5526 => 'Jamuria', 5527 => 'Jangipur',
                5528 => 'Jaygaon', 5529 => 'Jaynagar-Majilpur', 5530 => 'Jemari',
                5531 => 'Jemari Township', 5532 => 'Jetia', 5533 => 'Jhalida',
                5534 => 'Jhargram', 5535 => 'Jhorhat', 5536 => 'Jiaganj-Azimganj',
                5537 => 'Joka', 5538 => 'Jot Kamal', 5539 => 'Kachu Pukur',
                5540 => 'Kajora', 5541 => 'Kakdihi', 5542 => 'Kakdwip',
                5543 => 'Kalaikunda', 5544 => 'Kalara', 5545 => 'Kalimpong',
                5546 => 'Kaliyaganj', 5547 => 'Kalna', 5548 => 'Kalyani',
                5549 => 'Kamarhati', 5550 => 'Kanaipur', 5551 => 'Kanchrapara',
                5552 => 'Kandi', 5553 => 'Kanki', 5554 => 'Kankuria',
                5555 => 'Kantlia', 5556 => 'Kanyanagar', 5557 => 'Karimpur',
                5558 => 'Karsiyang', 5559 => 'Kasba', 5560 => 'Kasimbazar',
                5561 => 'Katwa', 5562 => 'Kaugachhi', 5563 => 'Kenda',
                5564 => 'Kendra Khottamdi', 5565 => 'Kendua', 5566 => 'Kesabpur',
                5567 => 'Khagrabari', 5568 => 'Khalia', 5569 => 'Khalor',
                5570 => 'Khandra', 5571 => 'Khantora', 5572 => 'Kharagpur',
                5573 => 'Kharagpur Railway Settlement', 5574 => 'Kharar', 5575 => 'Khardaha',
                5576 => 'Khari Mala Khagrabari', 5577 => 'Kharsarai', 5578 => 'Khatra',
                5579 => 'Khodarampur', 5580 => 'Kodalia', 5581 => 'Kolaghat',
                5582 => 'Kolaghat Thermal Power Project', 5583 => 'Kolkata', 5584 => 'Konardihi',
                5585 => 'Konnogar', 5586 => 'Krishnanagar', 5587 => 'Krishnapur',
                5588 => 'Kshidirpur', 5589 => 'Kshirpai', 5590 => 'Kulihanda',
                5591 => 'Kulti', 5592 => 'Kunustara', 5593 => 'Kuperskem',
                5594 => 'Madanpur', 5595 => 'Madhusudanpur', 5596 => 'Madhyamgram',
                5597 => 'Maheshtala', 5598 => 'Mahiari', 5599 => 'Mahikpur',
                5600 => 'Mahira', 5601 => 'Mahishadal', 5602 => 'Mainaguri',
                5603 => 'Makardaha', 5604 => 'Mal', 5605 => 'Malda',
                5606 => 'Mandarbani', 5607 => 'Mansinhapur', 5608 => 'Masila',
                5609 => 'Maslandapur', 5610 => 'Mathabhanga', 5611 => 'Mekliganj',
                5612 => 'Memari', 5613 => 'Midnapur', 5614 => 'Mirik',
                5615 => 'Monoharpur', 5616 => 'Mrigala', 5617 => 'Muragachha',
                5618 => 'Murgathaul', 5619 => 'Murshidabad', 5620 => 'Nabadhai Dutta Pukur',
                5621 => 'Nabagram', 5622 => 'Nabgram', 5623 => 'Nachhratpur Katabari',
                5624 => 'Nadia', 5625 => 'Naihati', 5626 => 'Nalhati',
                5627 => 'Nasra', 5628 => 'Natibpur', 5629 => 'Naupala',
                5630 => 'Navadwip', 5631 => 'Nebadhai Duttapukur', 5632 => 'New Barrackpore',
                5633 => 'Ni Barakpur', 5634 => 'Nibra', 5635 => 'Noapara',
                5636 => 'Nokpul', 5637 => 'North Barakpur', 5638 => 'Odlabari',
                5639 => 'Old Maldah', 5640 => 'Ondal', 5641 => 'Pairagachha',
                5642 => 'Palashban', 5643 => 'Panchla', 5644 => 'Panchpara',
                5645 => 'Pandua', 5646 => 'Pangachhiya', 5647 => 'Paniara',
                5648 => 'Panihati', 5649 => 'Panuhat', 5650 => 'Par Beliya',
                5651 => 'Parashkol', 5652 => 'Parasia', 5653 => 'Parbbatipur',
                5654 => 'Parui', 5655 => 'Paschim Jitpur', 5656 => 'Paschim Punro Para',
                5657 => 'Patrasaer', 5658 => 'Pattabong Tea Garden', 5659 => 'Patuli',
                5660 => 'Patulia', 5661 => 'Phulia', 5662 => 'Podara',
                5663 => 'Port Blair', 5664 => 'Prayagpur', 5665 => 'Pujali',
                5666 => 'Purba Medinipur', 5667 => 'Purba Tajpur', 5668 => 'Purulia',
                5669 => 'Raghudebbati', 5670 => 'Raghudebpur', 5671 => 'Raghunathchak',
                5672 => 'Raghunathpur', 5673 => 'Raghunathpur-Dankuni', 5674 => 'Raghunathpur-Magra',
                5675 => 'Raigachhi', 5676 => 'Raiganj', 5677 => 'Raipur',
                5678 => 'Rajarhat Gopalpur', 5679 => 'Rajpur', 5680 => 'Ramchandrapur',
                5681 => 'Ramjibanpur', 5682 => 'Ramnagar', 5683 => 'Rampur Hat',
                5684 => 'Ranaghat', 5685 => 'Raniganj', 5686 => 'Ratibati',
                5687 => 'Raypur', 5688 => 'Rishra', 5689 => 'Rishra Cantonment',
                5690 => 'Ruiya', 5691 => 'Sahajadpur', 5692 => 'Sahapur',
                5693 => 'Sainthia', 5694 => 'Salap', 5695 => 'Sankarpur',
                5696 => 'Sankrail', 5697 => 'Santoshpur', 5698 => 'Saontaidih',
                5699 => 'Sarenga', 5700 => 'Sarpi', 5701 => 'Satigachha',
                5702 => 'Serpur', 5703 => 'Shankhanagar', 5704 => 'Shantipur',
                5705 => 'Shrirampur', 5706 => 'Siduli', 5707 => 'Siliguri',
                5708 => 'Simla', 5709 => 'Singur', 5710 => 'Sirsha',
                5711 => 'Siuri', 5712 => 'Sobhaganj', 5713 => 'Sodpur',
                5714 => 'Sonamukhi', 5715 => 'Sonatikiri', 5716 => 'Srikantabati',
                5717 => 'Srirampur', 5718 => 'Sukdal', 5719 => 'Taherpur',
                5720 => 'Taki', 5721 => 'Talbandha', 5722 => 'Tamluk',
                5723 => 'Tarakeswar', 5724 => 'Tentulberia', 5725 => 'Tentulkuli',
                5726 => 'Thermal Power Project', 5727 => 'Tinsukia', 5728 => 'Titagarh',
                5729 => 'Tufanganj', 5730 => 'Ukhra', 5731 => 'Ula',
                5732 => 'Ulubaria', 5733 => 'Uttar Durgapur', 5734 => 'Uttar Goara',
                5735 => 'Uttar Kalas', 5736 => 'Uttar Kamakhyaguri', 5737 => 'Uttar Latabari',
                5738 => 'Uttar Mahammadpur', 5739 => 'Uttar Pirpur', 5740 => 'Uttar Raypur',
                5741 => 'Uttarpara-Kotrung',
            ],

            30 => [
                10181 => 'Midnapore', 10182 => 'Kharagpur', 10183 => 'Ghatal', 10184 => 'Jhargram', 10185 => 'Chandrakona',
            ],

            31 => [
                10191 => 'Puducherry', 10192 => 'Karaikal', 10193 => 'Mahe', 10194 => 'Yanam', 10195 => 'Oulgaret',
            ],

            // Punjab (32)
            32 => [
                1000 => 'Ludhiana', 1001 => 'Amritsar', 1002 => 'Jalandhar',
                1003 => 'Patiala', 1004 => 'Bathinda', 1005 => 'Mohali',
                1006 => 'Hoshiarpur', 1007 => 'Gurdaspur', 1008 => 'Fatehgarh Sahib',
                1009 => 'Ropar',
            ],

            // Rajasthan (33)
            33 => [
                600 => 'Jaipur', 601 => 'Jodhpur', 602 => 'Udaipur',
                603 => 'Kota', 604 => 'Ajmer', 605 => 'Bikaner',
                606 => 'Alwar', 607 => 'Bhilwara', 608 => 'Bharatpur',
                609 => 'Sikar',
            ],

            34 => [
                10201 => 'Gangtok', 10202 => 'Namchi', 10203 => 'Gyalshing', 10204 => 'Mangan', 10205 => 'Singtam',
            ],

            // Tamil Nadu (35)
            35 => [
                400 => 'Chennai', 401 => 'Coimbatore', 402 => 'Madurai',
                403 => 'Tiruchirappalli', 404 => 'Salem', 405 => 'Tirunelveli',
                406 => 'Tiruppur', 407 => 'Vellore', 408 => 'Erode',
                409 => 'Thoothukudi',
            ],

            // Telangana (36)
            36 => [
                1600 => 'Hyderabad', 1601 => 'Warangal', 1602 => 'Nizamabad',
                1603 => 'Karimnagar', 1604 => 'Khammam', 1605 => 'Ramagundam',
                1606 => 'Secunderabad', 1607 => 'Mahbubnagar', 1608 => 'Nalgonda',
                1609 => 'Adilabad',
            ],

            37 => [
                10211 => 'Agartala', 10212 => 'Dharmanagar', 10213 => 'Udaipur', 10214 => 'Belonia', 10215 => 'Kailashahar',
            ],

            // Uttar Pradesh (38)
            38 => [
                700 => 'Lucknow', 701 => 'Kanpur', 702 => 'Ghaziabad',
                703 => 'Agra', 704 => 'Varanasi', 705 => 'Meerut',
                706 => 'Allahabad (Prayagraj)', 707 => 'Noida', 708 => 'Bareilly',
                709 => 'Aligarh',
            ],

            39 => [
                10221 => 'Dehradun', 10222 => 'Haridwar', 10223 => 'Rishikesh', 10224 => 'Haldwani', 10225 => 'Roorkee',
            ],

            40 => [
                10231 => 'Hajipur', 10232 => 'Vaishali', 10233 => 'Mahnar Bazar', 10234 => 'Mahua', 10235 => 'Lalganj',
            ],
        ];

        return $cities[$stateId] ?? [];
    }
}
