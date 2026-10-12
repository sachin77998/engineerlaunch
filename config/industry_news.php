<?php

/*
 * "Latest Intelligence" topics. Each topic is a set of Google News searches (recent days only); new articles are
 * saved under the topic's category. Add a topic here and it appears on /latest after the next refresh.
 * Optional per topic: 'edition' (IN, US, GB), 'skills' (tags shown with the article) and 'audience' (who it affects).
 */
return [
    'endpoint' => 'https://news.google.com/rss/search',
    'locale' => ['hl' => 'en-IN', 'gl' => 'IN', 'ceid' => 'IN:en'],
    'editions' => [
        'IN' => ['hl' => 'en-IN', 'gl' => 'IN', 'ceid' => 'IN:en'],
        'US' => ['hl' => 'en-US', 'gl' => 'US', 'ceid' => 'US:en'],
        'GB' => ['hl' => 'en-GB', 'gl' => 'GB', 'ceid' => 'GB:en'],
    ],
    // Appended to every search so only fresh articles come back.
    'recency' => 'when:3d',
    'feeds' => [
        'gear' => [
            'name' => 'Gear Industry', 'category' => 'gear-industry', 'icon' => '⚙️',
            'queries' => ['gear manufacturing India', 'gear industry India', 'precision gears India', 'transmission gears India'],
        ],
        'forging' => [
            'name' => 'Forging Industry', 'category' => 'forging', 'icon' => '🔩',
            'queries' => ['forging industry India', 'automotive forging India', 'forging plant India', 'forging investment India'],
        ],
        'steel' => [
            'name' => 'Steel Industry', 'category' => 'steel-metals', 'icon' => '🏗️',
            'queries' => ['steel industry India', 'steel plant India', 'steel investment India', 'steel exports India'],
        ],
        'latest-tech' => [
            'name' => 'Latest in Tech', 'category' => 'latest-tech', 'icon' => '🚀',
            'queries' => ['latest technology news', 'computer science breakthrough', 'new technology launch', 'quantum computing news', 'cybersecurity news'],
            'skills' => ['Software Engineering', 'Cloud', 'AI/ML', 'Cybersecurity', 'Data Engineering'], 'audience' => 'software engineers and computer-science students',
        ],
        'ai-race' => [
            'name' => 'AI Race (US, China, Japan, India)', 'category' => 'ai-race', 'icon' => '🤖', 'edition' => 'US',
            'queries' => ['China AI race', 'Japan artificial intelligence investment', 'US AI race OpenAI Google', 'Nvidia AI chips', 'India AI mission'],
            'skills' => ['Machine Learning', 'Deep Learning', 'LLMs', 'Python', 'GPU Computing'], 'audience' => 'AI and data professionals',
        ],
        'data-centers' => [
            'name' => 'Data Centers & Cloud Investment', 'category' => 'data-centers', 'icon' => '🏢',
            'queries' => ['data center investment India', 'Oracle data center investment', 'Microsoft data center investment India', 'AWS investment India', 'Google cloud region India'],
            'skills' => ['Cloud Infrastructure', 'Networking', 'Linux', 'Kubernetes', 'Data Center Operations'], 'audience' => 'cloud, network and infrastructure engineers',
        ],
        'enterprise-software' => [
            'name' => 'CRM & Enterprise Software', 'category' => 'enterprise-software', 'icon' => '🧩',
            'queries' => ['Salesforce investment India', 'Informatica news', 'CRM software market', 'SAP India investment', 'Adobe India news'],
            'skills' => ['Salesforce', 'SAP', 'CRM', 'Data Integration', 'SaaS'], 'audience' => 'enterprise software and CRM professionals',
        ],
        'big-tech' => [
            'name' => 'Big Tech & Social Media', 'category' => 'big-tech', 'icon' => '🌐', 'edition' => 'US',
            'queries' => ['Meta Facebook news', 'WhatsApp new feature', 'Apple news', 'Google news announcement', 'Microsoft news'],
            'skills' => ['Product Management', 'Mobile Development', 'Data Science'], 'audience' => 'tech professionals',
        ],
        'startups' => [
            'name' => 'Startups & E-commerce Investment', 'category' => 'startups-ecommerce', 'icon' => '🛒',
            'queries' => ['Flipkart investment', 'Zomato news', 'Navi fintech news', 'Myntra news', 'Swiggy news', 'startup funding India'],
            'skills' => ['Product Management', 'Full Stack Development', 'Supply Chain', 'Growth Marketing'], 'audience' => 'startup and e-commerce professionals',
        ],
        'it-services' => [
            'name' => 'IT Services Growth & Outlook', 'category' => 'it-services-outlook', 'icon' => '💼',
            'queries' => ['IT companies growth forecast India', 'TCS Infosys Wipro revenue guidance', 'Accenture results', 'IT hiring outlook India', 'IT sector future impact AI'],
            'skills' => ['Java', 'Cloud', 'AI/ML', 'Testing', 'DevOps'], 'audience' => 'IT services employees and freshers',
        ],
        'banking-markets' => [
            'name' => 'Banking Shares & Markets', 'category' => 'banking-markets', 'icon' => '📈',
            'queries' => ['bank shares today India', 'Nifty Bank gainers losers', 'RBI policy banks', 'Sensex today', 'banking sector outlook India'],
            'skills' => ['Financial Analysis', 'Risk Management', 'Banking Operations'], 'audience' => 'banking and finance professionals',
        ],
        'fintech' => [
            'name' => 'Fintech & Payments', 'category' => 'fintech', 'icon' => '💳',
            'queries' => ['fintech funding India', 'UPI payments news', 'Paytm PhonePe Razorpay news', 'digital lending India'],
            'skills' => ['Payments', 'Java', 'Microservices', 'Risk Analytics', 'Compliance'], 'audience' => 'fintech and payments professionals',
        ],
        'consulting' => [
            'name' => 'Consulting & Audit Firms', 'category' => 'consulting-audit', 'icon' => '📊',
            'queries' => ['EY India news', 'Deloitte India news', 'KPMG India news', 'PwC India news', 'audit firms India regulation'],
            'skills' => ['Audit', 'Consulting', 'Financial Reporting', 'Data Analytics'], 'audience' => 'consultants, auditors and CA/MBA students',
        ],
        'automobile' => [
            'name' => 'Automobile & Car Launches', 'category' => 'automobile', 'icon' => '🚗',
            'queries' => ['new car launch India', 'car sales India', 'two wheeler launch India', 'auto parts industry India', 'car accessories market India'],
            'skills' => ['Automotive Engineering', 'Vehicle Design', 'Quality', 'Supply Chain'], 'audience' => 'automotive engineers and dealers',
        ],
        'ev' => [
            'name' => 'Electric Vehicles & Batteries', 'category' => 'electric-vehicles', 'icon' => '🔋',
            'queries' => ['EV market India', 'electric vehicle sales India', 'South Korea EV battery', 'EV battery plant India', 'EV charging infrastructure India'],
            'skills' => ['Battery Technology', 'Power Electronics', 'Embedded Systems', 'BMS'], 'audience' => 'EV, battery and electrical engineers',
        ],
        'mobiles-gadgets' => [
            'name' => 'Mobiles, Headphones & Gadgets', 'category' => 'mobiles-gadgets', 'icon' => '📱',
            'queries' => ['smartphone launch India', 'Vivo new phone', 'Samsung Galaxy launch', 'headphones launch India', 'laptop launch India', 'smartwatch launch India'],
            'skills' => ['Android', 'Embedded Systems', 'Hardware Design', 'Retail Sales'], 'audience' => 'electronics and mobile professionals',
        ],
        'mechanical-tech' => [
            'name' => 'Mechanical Engineering Technology', 'category' => 'mechanical-engineering', 'icon' => '🛠️',
            'queries' => ['latest technology mechanical engineering', 'manufacturing technology India', 'industrial robotics India', '3D printing manufacturing', 'smart factory India'],
            'skills' => ['CAD/CAM', 'Automation', 'Robotics', 'Additive Manufacturing', 'Lean Manufacturing'], 'audience' => 'mechanical engineers',
        ],
        'energy-fuel' => [
            'name' => 'Energy, Fuel & Petrol Pumps', 'category' => 'energy-fuel', 'icon' => '⛽',
            'queries' => ['petrol diesel price India', 'petrol pump dealers news', 'refinery news India', 'renewable energy investment India', 'green hydrogen India'],
            'skills' => ['Process Engineering', 'Energy Systems', 'Safety', 'Operations'], 'audience' => 'energy and oil & gas professionals',
        ],
        'trade-logistics' => [
            'name' => 'Import, Export & Logistics', 'category' => 'trade-logistics', 'icon' => '🚢',
            'queries' => ['India exports news', 'India imports news', 'logistics industry India', 'shipping freight rates', 'customs duty India'],
            'skills' => ['Supply Chain', 'Logistics', 'EXIM Documentation', 'Procurement'], 'audience' => 'logistics and supply-chain professionals',
        ],
        'industry-misc' => [
            'name' => 'Wood, Sports & Consumer Industries', 'category' => 'consumer-industries', 'icon' => '🏏',
            'queries' => ['furniture wood industry India', 'plywood industry India', 'sports goods industry India', 'Steelbird helmets', 'consumer durables India'],
            'skills' => ['Manufacturing', 'Sales', 'Quality', 'Design'], 'audience' => 'manufacturing and consumer-goods professionals',
        ],
        'us-market' => [
            'name' => 'US Market (Dallas, Texas & more)', 'category' => 'us-market', 'icon' => '🇺🇸', 'edition' => 'US',
            'queries' => ['US stock market today', 'Dallas tech jobs', 'Texas economy business', 'US layoffs hiring tech', 'Wall Street banks'],
            'skills' => ['Finance', 'Software Engineering', 'Data Analytics'], 'audience' => 'professionals targeting US roles',
        ],
        'uk-europe' => [
            'name' => 'UK & Europe (London, Scotland, Germany, Switzerland)', 'category' => 'uk-europe', 'icon' => '🇪🇺', 'edition' => 'GB',
            'queries' => ['London business news', 'Scotland economy', 'Germany industry news', 'Switzerland banks news', 'Europe economy outlook'],
            'skills' => ['Finance', 'Engineering', 'Consulting'], 'audience' => 'professionals targeting UK and European roles',
        ],
        'car-sales' => [
            'name' => 'Car Sales & Launches (Maruti, Hyundai, Tata, Toyota, Skoda, VW, BMW, Mercedes, Volvo)', 'category' => 'car-sales', 'icon' => '🚘',
            'queries' => ['Maruti Suzuki monthly sales', 'Hyundai India monthly sales', 'Tata Motors passenger vehicle sales', 'Toyota Kirloskar sales', 'Skoda Volkswagen India sales', 'Mahindra SUV sales', 'BMW Mercedes Volvo India sales', 'FADA retail sales cars', 'SIAM passenger vehicle wholesales', 'best selling cars India'],
            'skills' => ['Automotive Engineering', 'Dealer Sales', 'Supply Chain', 'Quality'], 'audience' => 'automotive engineers, dealers and sales professionals',
        ],
        'two-wheeler-sales' => [
            'name' => 'Bikes & Scooters (Hero, Honda, TVS, Bajaj, Ather, Royal Enfield, Jawa, Harley-Davidson)', 'category' => 'two-wheeler-sales', 'icon' => '🏍️',
            'queries' => ['Hero MotoCorp sales', 'Honda two wheeler sales India', 'TVS Motor sales', 'Bajaj Auto sales', 'Ather Energy sales', 'Royal Enfield sales', 'Jawa Yezdi news', 'Harley-Davidson India', 'best selling bikes India', 'electric scooter sales India'],
            'skills' => ['Automotive Engineering', 'EV Technology', 'Dealer Sales'], 'audience' => 'two-wheeler industry professionals',
        ],
        'tractor-sales' => [
            'name' => 'Tractors & Farm Equipment (Mahindra, Sonalika, Escorts Kubota, John Deere, Eicher, Massey, Kartar, Shaktimaan)', 'category' => 'tractor-sales', 'icon' => '🚜',
            'queries' => ['tractor sales India monthly', 'Mahindra tractor sales', 'Sonalika tractor sales', 'Escorts Kubota tractor sales', 'John Deere India', 'Eicher tractors', 'Massey Ferguson TAFE', 'Kartar tractors', 'Shaktimaan farm machinery', 'Kubota India'],
            'skills' => ['Agricultural Engineering', 'Dealer Sales', 'Production'], 'audience' => 'farm-equipment engineers and dealers',
        ],
        'mobile-brands' => [
            'name' => 'Mobiles & Wearables (Samsung, Apple, Realme, Oppo, Vivo, Nokia, Karbonn, boAt, Noise, Fire-Boltt, Titan)', 'category' => 'mobile-brands', 'icon' => '📲',
            'queries' => ['smartphone shipments India', 'Samsung India smartphone', 'Apple iPhone India sales', 'Realme Oppo Vivo India', 'Nokia Karbonn phones', 'boAt Noise Fire-Boltt smartwatch', 'Titan Eye Plus', 'wearables market India'],
            'skills' => ['Android', 'Hardware', 'Retail Sales', 'Supply Chain'], 'audience' => 'mobile and consumer-electronics professionals',
        ],
        'tyres-components' => [
            'name' => 'Tyres & Auto Components (MRF, Apollo, CEAT, JK, Michelin, Bridgestone, Goodyear, Continental, Yokohama, Bosch, TVS)', 'category' => 'tyres-components', 'icon' => '🛞',
            'queries' => ['MRF tyres news', 'Apollo Tyres news', 'CEAT tyres', 'JK Tyre news', 'Michelin Bridgestone Goodyear', 'Continental Yokohama tyres', 'Bosch India', 'auto components industry India'],
            'skills' => ['Rubber Technology', 'Manufacturing', 'Quality'], 'audience' => 'tyre and auto-component professionals',
        ],
        'electricals' => [
            'name' => 'Electricals, Wires & Cables (Havells, Crompton, Polycab, KEI, Finolex)', 'category' => 'electricals', 'icon' => '💡',
            'queries' => ['Havells news', 'Crompton Greaves Consumer', 'wires and cables industry India', 'Polycab KEI Finolex', 'consumer electricals India'],
            'skills' => ['Electrical Engineering', 'Sales', 'Manufacturing'], 'audience' => 'electrical engineers and dealers',
        ],
        'commodity-prices' => [
            'name' => 'Gold, Silver, Copper, Steel & Sand Prices', 'category' => 'commodity-prices', 'icon' => '🪙',
            'queries' => ['gold rate today per 10 gram', 'silver rate today per kg', 'copper price today', 'steel TMT price today', 'sand price news', 'MCX gold silver'],
            'skills' => ['Commodity Trading', 'Procurement', 'Finance'], 'audience' => 'buyers, procurement and finance professionals',
        ],
        'mutual-funds' => [
            'name' => 'Mutual Funds (ICICI Prudential, Nippon, HDFC, Axis, SBI, IDBI, Bandhan/IDFC)', 'category' => 'mutual-funds', 'icon' => '📊',
            'queries' => ['mutual fund news India', 'ICICI Prudential mutual fund', 'Nippon India mutual fund', 'HDFC mutual fund', 'Axis mutual fund', 'SBI mutual fund', 'Bandhan IDFC mutual fund', 'SIP inflows AMFI'],
            'skills' => ['Financial Analysis', 'Wealth Management', 'Investment Research'], 'audience' => 'finance and wealth-management professionals',
        ],
        'education' => [
            'name' => 'Schools, Colleges & Exams', 'category' => 'education-news', 'icon' => '🎓',
            'queries' => ['CBSE news', 'board exam results', 'college admissions India', 'IIT NIT news', 'university news India', 'JEE NEET news', 'school education news'],
            'skills' => ['Teaching', 'Counselling', 'Administration'], 'audience' => 'students, teachers and education professionals',
        ],
        'politics' => [
            'name' => 'Elections & Politics', 'category' => 'elections-politics', 'icon' => '🗳️',
            'queries' => ['election news India', 'Election Commission India', 'state assembly elections', 'Parliament news', 'government policy jobs'],
            'skills' => ['Public Policy', 'Journalism', 'Data Analysis'], 'audience' => 'policy, media and government-job aspirants',
        ],
        'cricket' => [
            'name' => 'Cricket & Sports', 'category' => 'cricket-sports', 'icon' => '🏏',
            'queries' => ['cricket news India', 'IPL news', 'Team India cricket', 'sports news India'],
            'skills' => ['Sports Management', 'Media', 'Marketing'], 'audience' => 'sports and media professionals',
        ],
        'entertainment' => [
            'name' => 'Bollywood, Music & Entertainment', 'category' => 'bollywood-music', 'icon' => '🎬',
            'queries' => ['Bollywood news', 'new songs release', 'box office collection', 'OTT releases India', 'music industry India'],
            'skills' => ['Media', 'Content', 'Marketing'], 'audience' => 'media and entertainment professionals',
        ],
        'gulf' => [
            'name' => 'Dubai & Gulf', 'category' => 'dubai-gulf', 'icon' => '🏙️',
            'queries' => ['Dubai business news', 'UAE jobs economy', 'Dubai real estate news', 'Saudi Arabia investment news'],
            'skills' => ['Sales', 'Engineering', 'Finance', 'Hospitality'], 'audience' => 'professionals targeting Gulf roles',
        ],
    ],
];
