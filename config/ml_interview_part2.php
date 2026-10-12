<?php

// Machine Learning interview series, part 2 (Q4–Q10). Rendered by learning.sql-case-studies (sections/blocks).
$code = fn (string $caption, string $source, string $language = 'python') => ['type' => 'code', 'caption' => $caption, 'language' => $language, 'source' => trim($source)];
$formula = fn (string $caption, string $source) => ['type' => 'code', 'caption' => $caption, 'language' => 'text', 'source' => trim($source)];
$table = fn (string $caption, array $columns, array $rows) => ['type' => 'table', 'caption' => $caption, 'columns' => $columns, 'rows' => $rows];
$text = fn (string ...$paragraphs) => ['type' => 'text', 'paragraphs' => $paragraphs];
$flow = fn (string $caption, array $steps) => ['type' => 'flow', 'caption' => $caption, 'steps' => $steps];
$ask = fn (array $items) => ['type' => 'questions', 'items' => $items];

$sections = [
    ['id' => 'q4', 'title' => 'Q4. What is the difference between Supervised, Unsupervised and Reinforcement Learning?', 'blocks' => [
        $text(
            '1. Supervised learning uses a dataset containing input features and known target labels. The algorithm learns the relationship between features and target so it can predict outcomes for new data.',
            'Example: a bank has historical records with income, credit score, loan amount and whether the customer defaulted. The historical outcome is the target label; the model learns patterns associated with default, then estimates risk for a new applicant.'
        ),
        $table('Labelled training data', ['Income', 'Credit score', 'Previous default'], [['₹30,000', 650, 'No'], ['₹20,000', 520, 'Yes'], ['₹80,000', 780, 'No'], ['₹25,000', 560, 'Yes']]),
        $text(
            'Supervised learning has two main categories. Regression predicts a continuous number such as house price or monthly sales. Classification predicts a category such as fraudulent versus legitimate. Common algorithms: Linear and Logistic Regression, Decision Trees, Random Forests, Support Vector Machines and Gradient Boosting.',
            '2. Unsupervised learning works with data that has no predefined target labels. The algorithm looks for structure, groups or patterns. A bank segmenting customers by transaction frequency, average balance, monthly spending and investment activity might discover: customers with frequent small transactions; customers with high balances and investment activity; customers with occasional large transactions. The groups are inferred from the data, and the business must still decide whether they are meaningful. Common algorithms: K-means, DBSCAN, hierarchical clustering and PCA.',
            '3. Reinforcement learning (RL) has an agent interacting with an environment, taking actions and receiving rewards or penalties. It learns a policy that maximises cumulative reward over time, for example a simulated warehouse robot learning to avoid obstacles and minimise delivery time.'
        ),
        $table('Reinforcement learning components', ['Component', 'Meaning'], [
            ['Agent', 'The learner or decision-maker'], ['Environment', 'The world the agent operates in'], ['State', 'The current situation'],
            ['Action', 'A decision available to the agent'], ['Reward', 'Feedback about the action'], ['Policy', 'The strategy for choosing actions'],
        ]),
        $formula('Expected discounted return', <<<'TXT'
G(t) = Σ (k = 0 … ∞) γᵏ · r(t + k + 1)

r = reward      γ (gamma) = discount factor: how strongly future rewards matter (0 ≤ γ < 1)
TXT),
        $table('Comparison', ['Feature', 'Supervised', 'Unsupervised', 'Reinforcement'], [
            ['Training feedback', 'Known target labels', 'No target labels', 'Rewards or penalties'],
            ['Primary objective', 'Predict outcomes', 'Discover patterns', 'Learn a decision policy'],
            ['Example', 'Predict loan default', 'Customer segmentation', 'Robot navigation'],
            ['Typical algorithms', 'Regression, Random Forest', 'K-means, PCA', 'Q-learning, PPO'],
        ]),
        $ask([
            ['Is customer segmentation supervised or unsupervised?', 'Usually unsupervised when the goal is to discover groups without predefined labels. If historical segment labels already exist and a model learns to predict them, it becomes supervised classification.'],
            ['Where does a job recommendation system fit?', 'Content-based ranking uses similarity between a profile and jobs (often unsupervised representations); learning from clicks and applications as labels is supervised; bandit-style exploration of what to show is reinforcement learning.'],
        ]),
    ]],
    ['id' => 'q5', 'title' => 'Q5. Explain Decision Trees in detail. How does the algorithm decide where to split?', 'blocks' => [
        $text('A Decision Tree is a supervised algorithm that predicts through a sequence of feature-based decisions, like a flowchart: each internal node tests a condition, each branch is an outcome, and each leaf produces a prediction. A classification tree predicts a class or class probabilities; a regression tree predicts a number, often the average target in a leaf.'),
        $flow('Illustrative loan-risk tree (not a valid lending policy)', ['Credit score ≥ 650?', 'Yes → check debt ratio', 'No → review other risk factors', 'Leaf → predicted risk class']),
        $text('How a split is chosen: the algorithm tries possible splits on features and measures how well each separates the data. For classification the common criteria are Gini impurity and entropy. A node with a single class has impurity 0; with two equally represented classes Gini is 0.5 and entropy is 1 bit.'),
        $formula('Gini, entropy and the weighted split impurity', <<<'TXT'
Gini   = 1 − Σₖ pₖ²                       pₖ = share of observations in class k
H      = − Σₖ pₖ · log₂(pₖ)

I(split) = (n_L / n) · I(L) + (n_R / n) · I(R)     weighted impurity of the left and right children
TXT),
        $text('The algorithm prefers splits that reduce impurity most, subject to its stopping rules and regularisation. Regression trees usually choose splits that minimise the sum of squared errors within the resulting leaves.'),
        $code('scikit-learn decision tree', <<<'TXT'
from sklearn.tree import DecisionTreeClassifier
from sklearn.metrics import accuracy_score

model = DecisionTreeClassifier(
    criterion="gini",
    max_depth=4,
    min_samples_leaf=10,
    random_state=42
)

model.fit(X_train, y_train)
predictions = model.predict(X_test)

print("Accuracy:", accuracy_score(y_test, predictions))
TXT),
        $table('Important parameters', ['Parameter', 'Effect'], [
            ['criterion', 'Impurity measure used to evaluate classification splits'],
            ['max_depth', 'Limits the depth of the tree'],
            ['min_samples_leaf', 'Minimum number of training examples in each leaf'],
            ['random_state', 'Makes results reproducible'],
        ]),
        $text('Overfitting: a very deep tree can memorise noise and unusual examples, scoring excellently on training data and poorly on new data. Use depth limits, minimum leaf sizes, pruning and validation on unseen data.'),
        $ask([
            ['Why are decision trees easy to interpret but often unstable?', 'Small trees are easy to read, but small changes in the training data can change the chosen splits and produce a very different tree. Ensembles such as Random Forests reduce this instability.'],
        ]),
    ]],
    ['id' => 'q6', 'title' => 'Q6. What is Random Forest, and how is it different from a Decision Tree?', 'blocks' => [
        $text(
            'Random Forest is an ensemble that combines many decision trees for a more robust prediction. Each tree is trained with randomness so that the trees do not all overfit the same patterns.',
            'Two central ideas: bootstrap sampling (each tree trains on a sample drawn with replacement) and random feature selection (each split considers only a random subset of features). Classification aggregates by majority vote or averaged probabilities; regression averages the numbers.'
        ),
        $table('Five trees vote on a transaction', ['Tree', 'Prediction'], [['Tree 1', 'Fraud'], ['Tree 2', 'Legitimate'], ['Tree 3', 'Fraud'], ['Tree 4', 'Fraud'], ['Tree 5', 'Legitimate']]),
        $text(
            'Three votes for fraud and two for legitimate gives a fraud prediction. Real fraud systems use probability thresholds and further controls rather than treating a vote as the final decision.',
            'Why it works: a single deep tree has high variance. Averaging trees that make partially independent errors reduces variance, and random feature selection keeps the trees less correlated. More trees do not fix poor data, leakage, biased labels or weak features.'
        ),
        $code('scikit-learn random forest', <<<'TXT'
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import classification_report

model = RandomForestClassifier(
    n_estimators=200,
    max_depth=12,
    min_samples_leaf=5,
    max_features="sqrt",
    class_weight="balanced",
    random_state=42,
    n_jobs=-1
)

model.fit(X_train, y_train)
predictions = model.predict(X_test)

print(classification_report(y_test, predictions))
TXT),
        $text('n_estimators=200 is the number of trees. class_weight="balanced" reweights classes by frequency; it does not guarantee better results on imbalanced data, so validate.'),
        $table('Random Forest vs Decision Tree', ['Feature', 'Decision Tree', 'Random Forest'], [
            ['Number of trees', 'One', 'Many'], ['Prediction', "One tree's output", 'Aggregated outputs'], ['Overfitting risk', 'Can be high', 'Often lower'],
            ['Interpretability', 'Easier to inspect', 'More complex'], ['Training cost', 'Usually lower', 'Usually higher'], ['Feature importance', 'Available', 'Available, with caveats'],
        ]),
        $ask([
            ['What is out-of-bag evaluation?', 'Each bootstrap sample leaves out some observations; those can evaluate the corresponding tree. Aggregating these out-of-bag predictions gives an internal performance estimate, useful but not a replacement for an independent test set.'],
        ]),
    ]],
    ['id' => 'q7', 'title' => 'Q7. Explain K-Nearest Neighbours (KNN) with a numerical example', 'blocks' => [
        $text('KNN predicts for a new observation from the labels or values of its nearest training examples: majority vote for classification, average for regression. It is called a lazy learner because it stores the training examples instead of learning compact parameters.'),
        $flow('How KNN works', ['Represent each example as a feature vector', 'Choose k', 'Compute distances to the training examples', 'Take the k closest', 'Vote (or average) their labels']),
        $formula('Euclidean distance', <<<'TXT'
d(x, y) = √( Σⱼ (xⱼ − yⱼ)² )

Two features:  d = √( (x₁ − y₁)² + (x₂ − y₂)² )
TXT),
        $text('Numerical example: the three nearest customers are A (Premium), B (Premium) and C (Standard). With k = 3, the vote predicts Premium. With k = 1 only the single nearest customer decides; with a very large k distant examples drown out the local pattern.'),
        $code('KNN with scaling in a pipeline', <<<'TXT'
from sklearn.pipeline import make_pipeline
from sklearn.preprocessing import StandardScaler
from sklearn.neighbors import KNeighborsClassifier
from sklearn.metrics import accuracy_score

model = make_pipeline(
    StandardScaler(),
    KNeighborsClassifier(n_neighbors=5)
)

model.fit(X_train, y_train)
predictions = model.predict(X_test)

print("Accuracy:", accuracy_score(y_test, predictions))
TXT),
        $text(
            'Scaling matters because KNN uses distances: a feature ranging 0–100,000 dominates one ranging 0–1. The pipeline fits the scaler on training data only, then applies it to the test data.',
            'Choosing k: try several values with cross-validation on the training data, pick one that balances under- and overfitting, then evaluate on an independent test set.'
        ),
        $table('Limitations', ['Limitation', 'Why'], [
            ['Slow prediction on large data', 'Many distances per query'], ['High-dimensional data', 'Neighbourhoods become less informative'],
            ['Irrelevant or unscaled features', 'Distort the distance'], ['Imbalanced classes', 'Majority classes dominate the vote'],
        ]),
        $ask([
            ['Is KNN suitable for millions of records?', 'A straightforward implementation is slow and memory-hungry. Approximate nearest-neighbour indexes, tree structures, dimensionality reduction or sampling help, depending on latency needs. This is exactly how vector search for job recommendations scales.'],
        ]),
    ]],
    ['id' => 'q8', 'title' => 'Q8. What is a Support Vector Machine (SVM), and how does the kernel trick work?', 'blocks' => [
        $text('An SVM finds the decision boundary that separates the classes with the widest margin to the nearest training examples. Those nearest examples, which define the boundary, are the support vectors. Support Vector Regression applies the idea to numbers.'),
        $formula('Linear SVM', <<<'TXT'
Boundary:          wᵀx + b = 0
Hard margin:       minimise ½‖w‖²   subject to   yᵢ(wᵀxᵢ + b) ≥ 1 for every training example

Minimising ‖w‖ maximises the margin. Soft margin adds slack variables and a penalty C to allow some violations.
TXT),
        $text(
            'The kernel trick: when classes cannot be separated by a straight hyperplane, a kernel computes similarities that correspond to a higher-dimensional feature space without ever building that space. Common kernels: linear, polynomial, RBF and sigmoid.',
            'In the RBF kernel γ controls how fast similarity drops with distance. A large γ makes very local boundaries and risks overfitting.'
        ),
        $formula('RBF kernel', 'K(x, z) = exp( −γ · ‖x − z‖² )'),
        $code('RBF SVM with scaling', <<<'TXT'
from sklearn.pipeline import make_pipeline
from sklearn.preprocessing import StandardScaler
from sklearn.svm import SVC

model = make_pipeline(
    StandardScaler(),
    SVC(
        kernel="rbf",
        C=1.0,
        gamma="scale",
        probability=True,
        random_state=42
    )
)

model.fit(X_train, y_train)
predictions = model.predict(X_test)
probabilities = model.predict_proba(X_test)
TXT),
        $text('probability=True adds an internal calibration step and extra training cost; check those probabilities before using them for risk decisions. SVMs suit small-to-medium datasets with good features; training can be expensive on very large data, and results depend heavily on scaling, kernel choice and tuning.'),
        $ask([
            ['SVM vs Logistic Regression?', 'Both can draw linear boundaries but optimise different objectives: logistic regression minimises log loss and gives probabilities naturally; SVM maximises the margin with hinge loss and needs calibration for probabilities.'],
        ]),
    ]],
    ['id' => 'q9', 'title' => "Q9. Explain Naive Bayes and Bayes' theorem. Why is the algorithm called naive?", 'blocks' => [
        $formula("Bayes' theorem", <<<'TXT'
P(C | X) = P(X | C) · P(C) / P(X)

P(C | X) = probability of class C given the features (posterior)
P(X | C) = likelihood of the features given the class
P(C)     = prior probability of the class
P(X)     = overall probability of the features
TXT),
        $text(
            'The classifier scores every class and picks the one with the highest posterior. It is called naive because it assumes the features are conditionally independent given the class. In spam filtering, the words "offer", "winner" and "free" are treated as independent given spam or not-spam; language does not really work that way, but the approximation often classifies well.'
        ),
        $formula('Scoring with the independence assumption', <<<'TXT'
score(C) ∝ P(C) · Πⱼ P(xⱼ | C)
log form (avoids underflow):  log P(C) + Σⱼ log P(xⱼ | C)
TXT),
        $code('Spam detection with MultinomialNB', <<<'TXT'
from sklearn.feature_extraction.text import CountVectorizer
from sklearn.naive_bayes import MultinomialNB
from sklearn.pipeline import make_pipeline

emails = [
    "win a free prize now",
    "claim your reward today",
    "meeting scheduled for tomorrow",
    "please review the project report"
]
labels = ["spam", "spam", "legitimate", "legitimate"]

model = make_pipeline(
    CountVectorizer(),
    MultinomialNB(alpha=1.0)
)

model.fit(emails, labels)

print(model.predict([
    "claim a free reward now",
    "please review the meeting report"
]))
TXT),
        $text('CountVectorizer turns text into word counts; MultinomialNB estimates class and word probabilities; alpha is additive (Laplace) smoothing so unseen words do not get zero probability. The result is a statistical classification, not a guarantee.'),
        $table('Types of Naive Bayes', ['Variant', 'Typical use'], [
            ['Gaussian', 'Continuous features with class-conditional normal distributions'], ['Multinomial', 'Word counts and other non-negative counts'],
            ['Bernoulli', 'Binary features such as "word present"'], ['Complement', 'Text classification, especially imbalanced data'],
        ]),
        $ask([
            ['If the independence assumption is wrong, does Naive Bayes fail?', 'Not necessarily: the class scores can still rank examples well. Its probabilities are often poorly calibrated, so calibrate and validate when probability accuracy matters.'],
        ]),
    ]],
    ['id' => 'q10', 'title' => 'Q10. What is the difference between Bagging, Boosting and Stacking?', 'blocks' => [
        $text(
            'All three are ensembles: they combine models to improve predictions, in different ways.',
            '1. Bagging (bootstrap aggregating) trains many models on bootstrap samples and aggregates them; Random Forest is the best-known example. The main aim is to reduce variance.',
            '2. Boosting builds the ensemble sequentially; each new learner improves the current ensemble, for example by fitting residual errors or following the gradient of the loss. Examples: AdaBoost, Gradient Boosting, XGBoost, LightGBM, CatBoost.',
            '3. Stacking trains several base models (e.g. Logistic Regression, Random Forest, Gradient Boosting) and a meta-model that learns how to combine their outputs. Train the meta-model on out-of-fold predictions to avoid leakage.'
        ),
        $formula('Aggregation rules', <<<'TXT'
Bagging (regression):   ŷ = (1/M) · Σₘ ŷₘ                M models, ŷₘ = prediction of model m
Gradient boosting:      Fₘ(x) = Fₘ₋₁(x) + η · hₘ(x)       hₘ = new learner, η = learning rate
TXT),
        $table('Comparison', ['Feature', 'Bagging', 'Boosting', 'Stacking'], [
            ['Training', 'Usually parallel', 'Sequential', 'Base models often parallel'],
            ['Mechanism', 'Aggregate independent models', 'Add learners that fix the ensemble', 'Learn a combination of outputs'],
            ['Common benefit', 'Reduce variance', 'Reduce bias / improve fit', 'Exploit complementary strengths'],
            ['Example', 'Random Forest', 'XGBoost', 'Random Forest + SVM + meta-model'],
        ]),
        $text('Real-world example: an online store predicting purchases might use a Random Forest for nonlinear interactions, Gradient Boosting for strong accuracy on tabular data, and stacking only if it measurably improves held-out results. Data volume, latency, interpretability and maintenance cost decide; more complex ensembles are not automatically better.'),
        $ask([
            ['Why is boosting sometimes more sensitive to noise than bagging?', 'Boosting keeps focusing on examples the ensemble gets wrong; if those are mislabelled or extreme, it spends effort fitting noise. Regularisation, shallow trees, small learning rates, early stopping and robust losses help.'],
        ]),
    ]],
];

return [
    'ml-interview-part-2' => [
        'title' => 'ML Interview Part 2: Learning Types, Trees, Forests, KNN, SVM, Naive Bayes & Ensembles',
        'view' => 'learning.sql-case-studies',
        'summary' => 'Supervised, unsupervised and reinforcement learning; how decision trees split; random forests; KNN; SVM and kernels; Naive Bayes; and bagging vs boosting vs stacking, with formulas, Python code and interview follow-ups.',
        'example_label' => 'Running examples: loan default, fraud detection, customer segmentation and spam filtering',
        'questions' => array_column($sections, 'title'),
        'sections' => $sections,
    ],
];
