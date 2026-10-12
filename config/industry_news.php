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
        'gulf' => [
            'name' => 'Dubai & Gulf', 'category' => 'dubai-gulf', 'icon' => '🏙️',
            'queries' => ['Dubai business news', 'UAE jobs economy', 'Dubai real estate news', 'Saudi Arabia investment news'],
            'skills' => ['Sales', 'Engineering', 'Finance', 'Hospitality'], 'audience' => 'professionals targeting Gulf roles',
        ],
    ],
];
