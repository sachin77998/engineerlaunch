<?php

/*
 * Role families for the job finder. A job matches a role when its stored role_family is that slug or when its
 * title contains one of the keywords (case-insensitive), so jobs imported before a role existed are still found.
 * 'includes' lets an umbrella role (Data & AI) cover its specific roles.
 */
return [
    'software-development' => ['label' => 'Software development', 'keywords' => ['developer', 'software engineer', 'programmer', 'sde ', 'sde-', 'sde1', 'sde2']],
    'frontend' => ['label' => 'Frontend', 'keywords' => ['frontend', 'front end', 'front-end', 'react', 'angular', 'vue', 'ui engineer', 'ui developer']],
    'backend' => ['label' => 'Backend', 'keywords' => ['backend', 'back end', 'back-end', 'server-side', 'spring boot', 'node.js', 'nodejs', 'golang', 'java developer', 'php developer', '.net developer', 'python developer']],
    'full-stack' => ['label' => 'Full stack', 'keywords' => ['full stack', 'full-stack', 'fullstack', 'mern', 'mean stack']],
    'quality-testing' => ['label' => 'Testing & QA', 'keywords' => ['qa ', 'qa-', 'quality assurance', 'tester', 'testing', 'sdet', 'automation test']],
    'devops-cloud' => ['label' => 'DevOps & cloud', 'keywords' => ['devops', 'sre', 'site reliability', 'cloud engineer', 'cloud architect', 'platform engineer', 'kubernetes', 'aws engineer', 'azure engineer']],
    'data-science' => ['label' => 'Data science & analytics', 'keywords' => ['data scien', 'data analy', 'analytics', 'business intelligence', 'bi developer', 'bi analyst', 'power bi', 'tableau', 'statistic', 'data engineer', 'quantitative analyst']],
    'ai-ml' => ['label' => 'AI & machine learning', 'keywords' => ['machine learning', 'ml engineer', 'mlops', 'ai engineer', 'ai/ml', 'ai ml', 'artificial intelligence', 'deep learning', 'computer vision', 'nlp', 'natural language', 'generative', 'genai', 'gen ai', 'llm', 'prompt engineer', 'ai developer', 'ai research', 'ai scientist']],
    'chatbot-ai' => ['label' => 'Chatbots & conversational AI', 'keywords' => ['chatbot', 'chat bot', 'conversational', 'dialogflow', 'rasa', 'virtual assistant', 'voice assistant', 'bot developer', 'copilot studio', 'power virtual agents', 'botpress', 'ai agent', 'agentic']],
    'cyber-security' => ['label' => 'Cyber security', 'keywords' => ['cyber', 'security analyst', 'security engineer', 'security architect', 'security consultant', 'information security', 'infosec', 'soc analyst', 'soc engineer', ' soc ', 'penetration', 'pentest', 'vapt', 'ethical hack', 'siem', 'threat', 'incident response', 'vulnerability', 'appsec', 'devsecops', 'grc', 'iam engineer', 'identity and access']],
    'data-ai' => ['label' => 'Data & AI (all)', 'keywords' => [], 'includes' => ['data-science', 'ai-ml', 'chatbot-ai']],
    'security' => ['label' => 'Security (all)', 'keywords' => [], 'includes' => ['cyber-security']],
];
