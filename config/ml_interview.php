<?php

// Machine Learning interview series, part 1 (Q1–Q3). Rendered by learning.sql-case-studies (sections/blocks).
$code = fn (string $caption, string $source, string $language = 'python') => ['type' => 'code', 'caption' => $caption, 'language' => $language, 'source' => trim($source)];
$formula = fn (string $caption, string $source) => ['type' => 'code', 'caption' => $caption, 'language' => 'text', 'source' => trim($source)];
$table = fn (string $caption, array $columns, array $rows) => ['type' => 'table', 'caption' => $caption, 'columns' => $columns, 'rows' => $rows];
$text = fn (string ...$paragraphs) => ['type' => 'text', 'paragraphs' => $paragraphs];
$flow = fn (string $caption, array $steps) => ['type' => 'flow', 'caption' => $caption, 'steps' => $steps];
$ask = fn (array $items) => ['type' => 'questions', 'items' => $items];

$sections = [
    ['id' => 'q1', 'title' => 'Q1. What is Machine Learning, and how does it work internally?', 'blocks' => [
        $text(
            'Machine Learning (ML) is a branch of artificial intelligence in which a computer system learns patterns from historical data and uses those patterns to make predictions or decisions on new data.',
            'In traditional programming, a developer explicitly writes rules. In machine learning, a developer provides training data and chooses an algorithm that learns the relationships between inputs and outputs.',
            'Example: a bank wants to predict whether a customer will repay a loan. Traditional programming might use rules such as: reject if the credit score is below 600; reject if monthly income is below ₹20,000; approve if the customer has no previous defaults. These rules can be useful, but they may not capture complicated relationships among income, existing debt, repayment history, employment stability and loan amount.',
            'A machine learning model can learn those relationships from historical loan records, subject to data quality, regulatory requirements and appropriate model validation.'
        ),
        $flow('How machine learning works, step by step', [
            '1. Collect historical data: income, credit history, debt, repayment outcomes',
            '2. Clean and prepare data: handle missing values, encode categories, scale features if needed',
            '3. Train the algorithm: learn model parameters by minimising a loss function',
            '4. Evaluate on unseen data: measure generalisation, not just training performance',
            '5. Deploy and monitor: generate predictions and watch for drift and errors',
        ]),
        $text('What happens during training? Each loan application is represented by a feature vector. The known historical outcome is y, where 1 means default and 0 means no default. The model computes a prediction from the features and its learned parameters.'),
        $formula('Feature vector and prediction', <<<'TXT'
x  = [income, credit score, debt]        input features
ŷ  = f_θ(x)                               the model's prediction

f  = the selected model or algorithm
θ  = the parameters learned during training
ŷ  = the predicted outcome (here, probability of default)
TXT),
        $text('The training algorithm compares predictions with actual outcomes using a loss function, then adjusts the parameters to reduce the loss. Gradient descent updates the parameters approximately as follows, where J is the loss, η (eta) is the learning rate and the gradient points in the direction in which the loss increases most rapidly, so we step the opposite way:'),
        $formula('Gradient descent update', <<<'TXT'
θ(t+1) = θ(t) − η · ∇θ J(θ(t))

Repeat until a stopping condition: maximum iterations reached, or no meaningful improvement.
TXT),
        $code('Practical Python example: train, test and report', <<<'TXT'
from sklearn.model_selection import train_test_split
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import classification_report

# X contains numerical features.
# y contains historical outcomes: 0 or 1.
X_train, X_test, y_train, y_test = train_test_split(
    X, y,
    test_size=0.2,
    random_state=42,
    stratify=y
)

model = LogisticRegression(max_iter=1000)

model.fit(X_train, y_train)

predictions = model.predict(X_test)

print(classification_report(y_test, predictions))
TXT),
        $text('This example assumes X and y have already been prepared. Real credit-risk systems need additional preprocessing, leakage checks, calibration, fairness assessments, validation and appropriate approval controls.'),
        $table('Traditional programming vs machine learning', ['', 'Traditional programming', 'Machine learning'], [
            ['Input', 'Rules + data', 'Data + known outcomes'],
            ['Output', 'Answers', 'A model (learned rules) that produces answers'],
            ['Changes when the world changes', 'A developer rewrites the rules', 'Retrain on newer data'],
            ['Explainability', 'Rules are explicit', 'Depends on the model; needs explanation tools'],
        ]),
        $ask([
            ['Why do we split data into training and testing sets?', 'The training set teaches the model patterns. The test set estimates how well the trained model performs on data it has not seen. Using the test set repeatedly to tune the model causes information leakage, so a separate validation set or cross-validation is used during model selection.'],
            ['What is the difference between an algorithm and a model?', 'An algorithm is the procedure used to learn patterns, such as gradient descent or decision-tree learning. A model is the fitted result containing learned parameters or structures that can make predictions.'],
            ['Can a model be 99% accurate and still be bad?', 'Yes. If only 1% of loan records are defaults, a model that predicts "no default" for everyone is 99% accurate while catching zero defaults. Precision, recall, PR-AUC, calibration and the financial cost of each error type are more informative.'],
        ]),
    ]],
    ['id' => 'q2', 'title' => 'Q2. Explain Linear Regression, including its mathematics and real-world applications', 'blocks' => [
        $text(
            'Linear Regression is a supervised machine learning algorithm used to predict a continuous numerical value: house prices, monthly sales, demand forecasts, delivery times.',
            'It assumes the target can be approximated by a linear combination of the input features. A company might predict monthly sales from advertising spend, the number of sales representatives and website traffic.'
        ),
        $formula('The model', <<<'TXT'
One feature:        ŷ = w·x + b
Many features:      ŷ = b + w₁x₁ + w₂x₂ + … + wₙxₙ

x = input feature    w = coefficient (weight)    b = intercept (bias)    ŷ = predicted value
TXT),
        $text('How does it learn? The model starts with initial coefficients, predicts, measures the errors against the known values, and finds the coefficients that minimise the loss. The usual loss is Mean Squared Error. Squaring penalises large mistakes more heavily and stops positive and negative errors from cancelling out.'),
        $formula('Mean Squared Error', <<<'TXT'
J(w, b) = (1/n) · Σᵢ ( yᵢ − (w·xᵢ + b) )²
TXT),
        $table('Two ways to fit the coefficients', ['Method', 'How it works', 'When to use'], [
            ['Ordinary Least Squares (OLS)', 'Computes the coefficients that minimise the sum of squared residuals in closed form, when the matrix conditions hold', 'Small and medium datasets'],
            ['Gradient descent', 'Iteratively moves the coefficients in the direction that reduces the loss', 'Very large datasets, or as part of more complex models'],
        ]),
        $text('Numerical example. Suppose a simplified fitted model predicts house prices as: Price (lakhs) = 10 + 5 × Bedrooms.'),
        $table('Predicted price by bedrooms', ['Bedrooms', 'Predicted price'], [[1, '₹15 lakh'], [2, '₹20 lakh'], [3, '₹25 lakh'], [4, '₹30 lakh']]),
        $text('The coefficient 5 means the model associates each additional bedroom with ₹5 lakh more, given the features in this simplified model. It does not establish that adding a bedroom will causally raise a real house\'s value by that amount.'),
        $code('Fit and evaluate with scikit-learn', <<<'TXT'
import numpy as np
from sklearn.linear_model import LinearRegression
from sklearn.metrics import mean_squared_error, r2_score

# Feature: number of bedrooms
X = np.array([[1], [2], [3], [4], [5]])
y = np.array([15, 20, 25, 30, 35])

model = LinearRegression()
model.fit(X, y)

prediction = model.predict([[3]])

print("Coefficient:", model.coef_[0])
print("Intercept:", model.intercept_)
print("Predicted price:", prediction[0])

print("Training MSE:", mean_squared_error(y, model.predict(X)))
print("Training R2:", r2_score(y, model.predict(X)))
TXT),
        $text('Expected fitted relationship: coefficient approximately 5 and intercept approximately 10. This tiny example demonstrates the mechanics; it does not evaluate real-world predictive performance.'),
        $table('Assumptions and limitations', ['Issue', 'Effect', 'Mitigation'], [
            ['Nonlinear relationships', 'Poor fit', 'Polynomial features, tree models'],
            ['Extreme outliers', 'Coefficients pulled heavily', 'Robust regression, clean or cap outliers'],
            ['Highly correlated features', 'Unstable coefficients', 'Ridge / Lasso regularisation, drop duplicates'],
            ['High training R²', 'Does not guarantee good predictions on new data', 'Evaluate on a held-out test set'],
        ]),
        $text('Classical statistical inference also relies on assumptions such as linearity, independent errors and constant error variance. Regularised versions (Ridge, Lasso, Elastic Net) help manage overfitting and correlated features.'),
        $ask([
            ['What is the difference between MSE and MAE?', 'MAE averages absolute errors; MSE averages squared errors. MAE is less sensitive to large errors, MSE penalises them more strongly. Choose based on the error characteristics and the business cost of large mistakes.'],
            ['What does R² measure?', 'The proportion of variance in the target explained by the model, relative to always predicting the mean. It can be negative on test data when the model is worse than the mean.'],
            ['Why scale features before gradient descent?', 'Features on very different scales make the loss surface elongated, so gradient descent converges slowly; scaling (standardisation) makes steps balanced.'],
        ]),
    ]],
    ['id' => 'q3', 'title' => 'Q3. Explain Logistic Regression. Why is it used for classification instead of Linear Regression?', 'blocks' => [
        $text(
            'Despite its name, Logistic Regression is primarily used for classification. It estimates the probability that an observation belongs to a particular class.',
            'A bank predicting whether a customer will default needs a class (default or no default), but the model first estimates a probability. Linear Regression can output values below 0 or above 1, which are not valid probabilities. Logistic Regression fixes this by passing a linear score through the sigmoid function.'
        ),
        $formula('Linear score, then sigmoid', <<<'TXT'
z = w₁x₁ + w₂x₂ + … + wₙxₙ + b

p = σ(z) = 1 / (1 + e^(−z))         always strictly between 0 and 1
TXT),
        ['type' => 'sigmoid', 'caption' => 'Explore the sigmoid function'],
        $text('Training minimises binary cross-entropy (log loss). yᵢ is the actual outcome and pᵢ the predicted probability. The loss heavily penalises confident predictions that are wrong; an iterative optimiser adjusts the coefficients to reduce it.'),
        $formula('Binary cross-entropy (log loss)', <<<'TXT'
J = −(1/n) · Σᵢ [ yᵢ·log(pᵢ) + (1 − yᵢ)·log(1 − pᵢ) ]
TXT),
        $text('How classification happens: if the model estimates a default probability of 0.72 and the decision threshold is 0.50, the application is classified as default risk because 0.72 ≥ 0.50. The threshold is a separate business decision from estimating the probability, chosen from validated performance, the cost of each error, fairness and regulatory obligations. A prediction should not decide a loan automatically without suitable controls.'),
        $code('Probabilities and ROC-AUC', <<<'TXT'
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import roc_auc_score

# X: prepared customer features
# y: historical binary outcome
model = LogisticRegression(max_iter=1000)
model.fit(X_train, y_train)

# Estimated probability of class 1
probabilities = model.predict_proba(X_test)[:, 1]

# Default classification threshold is normally 0.5
predictions = model.predict(X_test)

print("First five probabilities:", probabilities[:5])
print("ROC-AUC:", roc_auc_score(y_test, probabilities))
TXT),
        $text('For imbalanced outcomes also examine precision, recall, PR-AUC and probability calibration. ROC-AUC alone does not tell you whether a particular threshold is suitable for deployment.'),
        $table('Linear Regression vs Logistic Regression', ['Feature', 'Linear Regression', 'Logistic Regression'], [
            ['Main purpose', 'Predict numerical values', 'Estimate class probabilities'],
            ['Typical output', 'Any real number', 'Probability between 0 and 1'],
            ['Common loss', 'Squared error', 'Log loss'],
            ['Example', 'Predict monthly sales', 'Predict probability of default'],
            ['Decision boundary', 'Not inherently a classifier', 'Classifies using a chosen threshold'],
        ]),
        $ask([
            ['Why is it called regression if it performs classification?', 'It models the log-odds of the positive class, log(p / (1 − p)), as a linear function of the features. It is a regression model for log-odds, commonly used to solve classification problems.'],
            ['How would you handle a 1% default rate?', 'Stratified splits, class weights or resampling, threshold tuning on a validation set, and metrics such as recall, precision and PR-AUC instead of accuracy.'],
            ['What does a coefficient mean in logistic regression?', 'A one-unit increase in that feature multiplies the odds of the positive class by e^w, holding the other features fixed.'],
        ]),
    ]],
    ['id' => 'series', 'title' => 'Series plan', 'blocks' => [
        $table('Upcoming parts', ['Topic', 'Questions and concepts'], [
            ['Machine Learning', 'Polynomial regression, Ridge, Lasso, Elastic Net, KNN, SVM, Naive Bayes, decision trees, random forests, bagging, boosting, XGBoost, LightGBM, CatBoost, K-means, hierarchical clustering, DBSCAN, PCA, neural networks, CNNs, RNNs, reinforcement learning, model evaluation'],
            ['LLMs', 'Tokenization, embeddings, self-attention, transformers, positional information, pretraining, next-token prediction, fine-tuning, LoRA, quantization, RAG, vector search, hallucinations, prompt injection, tool calling, agents, inference optimisation, evaluation'],
            ['React', 'Components, props, state, hooks, rendering, reconciliation, effects, Context, Redux, routing, Suspense, Server Components, performance, testing, security, architecture'],
            ['Node.js', 'V8, event loop phases, libuv, callbacks, promises, streams, buffers, worker threads, clustering, Express APIs, authentication, database connections, queues, caching, testing, microservices'],
            ['TypeScript', 'Type inference, structural typing, interfaces, type aliases, generics, unions, narrowing, utility types, conditional and mapped types, keyof, typeof, infer, decorators, compiler options'],
        ]),
    ]],
];

return [
    'ml-interview-part-1' => [
        'title' => 'ML Interview Part 1: Foundations, Linear & Logistic Regression',
        'view' => 'learning.sql-case-studies',
        'summary' => 'How machine learning learns from data, then linear regression for numbers and logistic regression for probabilities, each with the mathematics, Python code, limitations and interview follow-ups.',
        'example_label' => 'Running examples: loan default prediction for a bank and house price estimation',
        'questions' => array_column($sections, 'title'),
        'sections' => $sections,
    ],
];
