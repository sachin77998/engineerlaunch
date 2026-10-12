<?php

// Machine Learning interview series, part 3 (Q11–Q20). Rendered by learning.sql-case-studies (sections/blocks).
$code = fn (string $caption, string $source, string $language = 'python') => ['type' => 'code', 'caption' => $caption, 'language' => $language, 'source' => trim($source)];
$formula = fn (string $caption, string $source) => ['type' => 'code', 'caption' => $caption, 'language' => 'text', 'source' => trim($source)];
$table = fn (string $caption, array $columns, array $rows) => ['type' => 'table', 'caption' => $caption, 'columns' => $columns, 'rows' => $rows];
$text = fn (string ...$paragraphs) => ['type' => 'text', 'paragraphs' => $paragraphs];
$flow = fn (string $caption, array $steps) => ['type' => 'flow', 'caption' => $caption, 'steps' => $steps];
$ask = fn (array $items) => ['type' => 'questions', 'items' => $items];

$sections = [
    ['id' => 'q11', 'title' => 'Q11. What is Gradient Descent and what are its variants?', 'blocks' => [
        $text(
            'Gradient descent is an optimisation algorithm that finds the model parameters (weights) that minimise a loss function. The gradient is the vector of partial derivatives of the loss with respect to each parameter; it points in the direction where the loss increases fastest. Moving a small step in the opposite direction reduces the loss.',
            'Analogy: you stand on a foggy hill and want to reach the valley. You cannot see the valley, but you can feel the slope under your feet. You take a step downhill, feel the slope again, and repeat. The size of each step is the learning rate.'
        ),
        $formula('Update rule', <<<'TXT'
θ ← θ − η · ∇J(θ)

θ = parameters (weights and bias)    η = learning rate    ∇J(θ) = gradient of the loss J

Linear regression with MSE:  J(w, b) = (1/n) Σ (ŷᵢ − yᵢ)²,  ŷᵢ = w·xᵢ + b
∂J/∂w = (2/n) Σ (ŷᵢ − yᵢ) · xᵢ        ∂J/∂b = (2/n) Σ (ŷᵢ − yᵢ)
TXT),
        $flow('One training loop', [
            'Initialise weights (zeros or small random values).',
            'Predict ŷ for the training examples with the current weights.',
            'Compute the loss (e.g. mean squared error or log loss).',
            'Compute the gradient of the loss with respect to each weight.',
            'Update every weight: w = w − learning_rate × gradient.',
            'Repeat until the loss stops decreasing (convergence) or the epoch limit is reached.',
        ]),
        $code('Gradient descent for linear regression from scratch', <<<'PY'
import numpy as np

X = np.array([1, 2, 3, 4, 5], dtype=float)      # years of experience
y = np.array([3, 5, 7, 9, 11], dtype=float)     # salary in lakhs (y = 2x + 1)

w, b, lr = 0.0, 0.0, 0.01
for epoch in range(2000):
    y_hat = w * X + b
    error = y_hat - y
    dw = (2 / len(X)) * np.dot(error, X)
    db = (2 / len(X)) * error.sum()
    w -= lr * dw
    b -= lr * db

print(round(w, 3), round(b, 3))   # ≈ 2.0, 1.0
PY),
        $table('Variants', ['Variant', 'Data per update', 'Pros', 'Cons'], [
            ['Batch GD', 'Whole dataset', 'Stable, exact gradient', 'Slow and memory-heavy on large data'],
            ['Stochastic GD (SGD)', 'One example', 'Fast updates, can escape shallow local minima', 'Noisy, zig-zag path'],
            ['Mini-batch GD', '32–512 examples', 'Balance of speed and stability; uses GPUs well', 'Batch size is another hyperparameter'],
            ['Momentum', 'Mini-batch + velocity', 'Speeds up along consistent directions, damps oscillation', 'Extra hyperparameter β'],
            ['RMSProp', 'Mini-batch + per-weight scaling', 'Adapts step size per parameter', 'Can still need tuning'],
            ['Adam', 'Momentum + RMSProp', 'Good default for deep learning', 'May generalise slightly worse than tuned SGD'],
        ]),
        $formula('Momentum and Adam', <<<'TXT'
Momentum:  v ← β·v + ∇J(θ);   θ ← θ − η·v                  (β ≈ 0.9)

Adam:      m ← β₁·m + (1 − β₁)·g          (mean of gradients)
           s ← β₂·s + (1 − β₂)·g²         (mean of squared gradients)
           m̂ = m / (1 − β₁ᵗ),  ŝ = s / (1 − β₂ᵗ)   (bias correction)
           θ ← θ − η · m̂ / (√ŝ + ε)       (β₁ = 0.9, β₂ = 0.999, ε = 1e-8)
TXT),
        $text(
            'Choosing the learning rate: too small and training crawls; too large and the loss oscillates or explodes (diverges). Plot the training loss per epoch. Common practice is to start with 0.001 for Adam or 0.01–0.1 for SGD and use a schedule (step decay, cosine decay or warm-up) that lowers it over time.',
            'Feature scaling matters: if one feature ranges 0–1 and another 0–1,000,000, the loss surface becomes a long narrow valley and gradient descent zig-zags. Standardise features (mean 0, standard deviation 1) before training.'
        ),
        $ask([
            ['Does gradient descent always find the global minimum?', 'Only for convex losses such as linear regression with MSE or logistic regression with log loss. Neural network losses are non-convex; gradient descent finds a good local minimum or saddle region, which in practice usually generalises well.'],
            ['What is an epoch versus an iteration?', 'An epoch is one full pass over the training data. An iteration is one weight update. With 10,000 examples and batch size 100, one epoch is 100 iterations.'],
            ['What are vanishing and exploding gradients?', 'In deep networks, gradients are multiplied layer by layer during backpropagation. Repeated multiplication by small numbers makes them vanish (early layers stop learning); by large numbers makes them explode. Fixes: ReLU activations, careful initialisation (He/Xavier), batch normalisation, residual connections and gradient clipping.'],
        ]),
    ]],

    ['id' => 'q12', 'title' => 'Q12. What is the Bias–Variance trade-off?', 'blocks' => [
        $text(
            'The expected prediction error of a model on new data can be split into three parts: bias, variance and irreducible noise.',
            'Bias is error from wrong or overly simple assumptions. A straight line fitted to curved data has high bias: it misses the pattern on training and test data alike.',
            'Variance is error from sensitivity to the particular training sample. A very deep decision tree memorises the training data; train it on a slightly different sample and its predictions change a lot.',
            'Noise is randomness in the data itself that no model can remove.'
        ),
        $formula('Decomposition (squared error)', <<<'TXT'
E[(y − f̂(x))²] = Bias[f̂(x)]² + Var[f̂(x)] + σ²

Bias[f̂(x)] = E[f̂(x)] − f(x)     how far the average model is from the truth
Var[f̂(x)]  = E[(f̂(x) − E[f̂(x)])²]  how much the model changes between training sets
σ²          = irreducible noise
TXT),
        $text('Analogy: archery. High bias, low variance: every arrow lands close together but far from the bullseye. Low bias, high variance: arrows scatter all around the bullseye. The goal is arrows clustered on the bullseye. As model complexity increases, bias falls and variance rises; total error is U-shaped and the best model sits at the bottom of the U.'),
        $table('Diagnosing from training and validation error', ['Training error', 'Validation error', 'Diagnosis', 'What to do'], [
            ['High', 'High (similar)', 'High bias (underfitting)', 'More features, more complex model, less regularisation, train longer'],
            ['Low', 'Much higher', 'High variance (overfitting)', 'More data, regularisation, simpler model, dropout, early stopping, bagging'],
            ['Low', 'Low (close)', 'Good fit', 'Check on a held-out test set and monitor in production'],
        ]),
        $table('How common models sit', ['Model', 'Typical bias', 'Typical variance'], [
            ['Linear / Logistic Regression', 'High', 'Low'], ['KNN with small k', 'Low', 'High'], ['KNN with large k', 'Higher', 'Lower'],
            ['Deep decision tree', 'Low', 'High'], ['Random Forest', 'Low', 'Reduced by averaging'], ['Boosting', 'Reduced step by step', 'Controlled with learning rate, depth, early stopping'],
        ]),
        $ask([
            ['How does regularisation affect bias and variance?', 'It adds a penalty on large weights (L1/L2), restricting the model. Variance goes down, bias goes up slightly. The regularisation strength (λ or C) is tuned with cross-validation to find the best balance.'],
            ['Does more data reduce bias or variance?', 'Mainly variance: the model sees more examples and is less sensitive to any single one. More data does not fix a model that is too simple (high bias).'],
        ]),
    ]],

    ['id' => 'q13', 'title' => 'Q13. What are Overfitting and Underfitting, and how do you prevent them?', 'blocks' => [
        $text(
            'Overfitting: the model learns the training data too well, including noise and coincidences, and performs poorly on new data. Example: a student memorises last year\'s question paper word for word and fails when the questions are rephrased.',
            'Underfitting: the model is too simple to capture the real pattern and performs poorly even on training data. Example: a student who only read the chapter titles.'
        ),
        $table('Symptoms', ['', 'Underfitting', 'Good fit', 'Overfitting'], [
            ['Training accuracy', 'Low (e.g. 65%)', 'High (92%)', 'Very high (99.8%)'],
            ['Validation accuracy', 'Low (63%)', 'High (90%)', 'Much lower (78%)'],
            ['Learning curve', 'Both curves plateau high in loss', 'Curves converge at low loss', 'Gap between curves keeps widening'],
        ]),
        $flow('Preventing overfitting', [
            'Get more training data or use data augmentation (images: flips, crops; text: back-translation).',
            'Use cross-validation to measure performance honestly.',
            'Regularise: L2 (Ridge) shrinks weights, L1 (Lasso) sets some to zero, Elastic Net combines both.',
            'Simplify the model: fewer features, shallower trees (max_depth, min_samples_leaf), fewer layers.',
            'Early stopping: stop training when validation loss stops improving.',
            'Dropout in neural networks: randomly switch off neurons during training.',
            'Ensembles: bagging (Random Forest) averages away variance.',
            'Remove leakage: make sure no feature contains information from the future or from the target.',
        ]),
        $formula('Regularised loss', <<<'TXT'
Ridge (L2):  J = MSE + λ · Σ wⱼ²
Lasso (L1):  J = MSE + λ · Σ |wⱼ|
Elastic Net: J = MSE + λ₁ · Σ |wⱼ| + λ₂ · Σ wⱼ²
TXT),
        $code('Seeing overfitting with polynomial degree', <<<'PY'
import numpy as np
from sklearn.pipeline import make_pipeline
from sklearn.preprocessing import PolynomialFeatures
from sklearn.linear_model import LinearRegression, Ridge
from sklearn.model_selection import train_test_split
from sklearn.metrics import mean_squared_error

rng = np.random.default_rng(0)
X = np.sort(rng.uniform(0, 1, 40)).reshape(-1, 1)
y = np.sin(2 * np.pi * X).ravel() + rng.normal(0, 0.2, 40)
X_tr, X_te, y_tr, y_te = train_test_split(X, y, random_state=0)

for degree in (1, 4, 15):
    model = make_pipeline(PolynomialFeatures(degree), LinearRegression()).fit(X_tr, y_tr)
    print(degree, mean_squared_error(y_tr, model.predict(X_tr)), mean_squared_error(y_te, model.predict(X_te)))
# degree 1: both errors high (underfit); degree 4: both low; degree 15: train ≈ 0, test large (overfit)

ridge = make_pipeline(PolynomialFeatures(15), Ridge(alpha=1e-3)).fit(X_tr, y_tr)   # regularisation tames degree 15
PY),
        $ask([
            ['What is data leakage and why does it look like great performance?', 'Leakage is when training features contain information unavailable at prediction time, e.g. scaling with statistics from the whole dataset, or a "refund_issued" column when predicting fraud. Validation scores look excellent, then the model fails in production. Fit preprocessing inside a Pipeline on training folds only and split time-series data by time.'],
        ]),
    ]],

    ['id' => 'q14', 'title' => 'Q14. What is Cross-Validation and how do you tune hyperparameters?', 'blocks' => [
        $text(
            'A single train/test split gives a noisy estimate: a lucky split looks great, an unlucky one looks bad. K-fold cross-validation splits the training data into k equal folds, trains k times, each time holding out a different fold for validation, and averages the k scores.',
            'Parameters are learned from data (weights, tree split points). Hyperparameters are set before training (learning rate, max_depth, number of trees, k in KNN, C in SVM). Cross-validation is how we choose hyperparameters without touching the final test set.'
        ),
        $table('5-fold cross-validation', ['Round', 'Fold 1', 'Fold 2', 'Fold 3', 'Fold 4', 'Fold 5'], [
            [1, 'Validate', 'Train', 'Train', 'Train', 'Train'], [2, 'Train', 'Validate', 'Train', 'Train', 'Train'],
            [3, 'Train', 'Train', 'Validate', 'Train', 'Train'], [4, 'Train', 'Train', 'Train', 'Validate', 'Train'],
            [5, 'Train', 'Train', 'Train', 'Train', 'Validate'],
        ]),
        $table('Which CV to use', ['Technique', 'When'], [
            ['K-fold (k = 5 or 10)', 'General regression and balanced classification'],
            ['Stratified K-fold', 'Classification, especially imbalanced classes: every fold keeps the class ratio'],
            ['Group K-fold', 'Several rows per customer/patient: keep all rows of a group in one fold'],
            ['TimeSeriesSplit', 'Time-ordered data: always train on the past and validate on the future'],
            ['Leave-one-out', 'Very small datasets; expensive'],
            ['Nested CV', 'Unbiased estimate when tuning: inner loop tunes, outer loop evaluates'],
        ]),
        $code('Grid search and randomized search', <<<'PY'
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import GridSearchCV, RandomizedSearchCV, StratifiedKFold, train_test_split
from scipy.stats import randint

X_train, X_test, y_train, y_test = train_test_split(X, y, stratify=y, test_size=0.2, random_state=42)
cv = StratifiedKFold(n_splits=5, shuffle=True, random_state=42)

grid = GridSearchCV(RandomForestClassifier(random_state=42),
                    {"n_estimators": [100, 300], "max_depth": [5, 10, None]},
                    cv=cv, scoring="f1", n_jobs=-1).fit(X_train, y_train)

rand = RandomizedSearchCV(RandomForestClassifier(random_state=42),
                          {"n_estimators": randint(100, 600), "max_depth": randint(3, 20), "min_samples_leaf": randint(1, 10)},
                          n_iter=30, cv=cv, scoring="f1", random_state=42, n_jobs=-1).fit(X_train, y_train)

print(grid.best_params_, grid.best_score_)
print(rand.best_estimator_.score(X_test, y_test))   # test set used once, at the very end
PY),
        $table('Tuning strategies', ['Strategy', 'How it works', 'Use when'], [
            ['Grid search', 'Tries every combination', 'Few hyperparameters, small ranges'],
            ['Random search', 'Samples combinations randomly', 'Many hyperparameters; usually finds good values faster'],
            ['Bayesian optimisation (Optuna, Hyperopt)', 'Uses past results to choose the next trial', 'Expensive models such as deep nets or large boosting'],
            ['Early stopping / Hyperband', 'Stops poor trials early', 'Iterative training (boosting, neural nets)'],
        ]),
        $ask([
            ['Why must preprocessing be inside the cross-validation loop?', 'If you scale or impute on the full training set first, statistics from the validation fold leak into training. Put the scaler, imputer and model in one Pipeline and pass the pipeline to GridSearchCV.'],
            ['Why not use the test set to choose hyperparameters?', 'Then the test set becomes part of training decisions and its score is optimistically biased. Keep it untouched until the final evaluation.'],
        ]),
    ]],

    ['id' => 'q15', 'title' => 'Q15. How does K-Means clustering work?', 'blocks' => [
        $text(
            'K-means is an unsupervised algorithm that groups n data points into k clusters so that each point belongs to the cluster with the nearest centroid (mean). It minimises the within-cluster sum of squared distances, also called inertia.',
            'Example: a retail chain clusters customers by annual spend and visits per month. K-means might find three groups: occasional low spenders, regular mid spenders and frequent high spenders, each receiving a different marketing offer.'
        ),
        $flow('Algorithm (Lloyd\'s)', [
            'Choose k and place k initial centroids (k-means++ spreads them out for better results).',
            'Assignment step: assign every point to its nearest centroid (Euclidean distance).',
            'Update step: move every centroid to the mean of the points assigned to it.',
            'Repeat assignment and update until assignments stop changing or the movement is below a tolerance.',
        ]),
        $formula('Objective', <<<'TXT'
minimise  J = Σ (j = 1 … k) Σ (xᵢ ∈ Cⱼ) ‖xᵢ − μⱼ‖²

Cⱼ = points in cluster j      μⱼ = centroid (mean) of cluster j
Time per iteration: O(n · k · d)   n = points, d = features
TXT),
        $table('Worked example (1-D, k = 2)', ['Step', 'Centroids', 'Cluster A', 'Cluster B'], [
            ['Start', 'μA = 2, μB = 10', '—', '—'],
            ['Assign', '', '1, 2, 3, 4', '9, 10, 12'],
            ['Update', 'μA = 2.5, μB = 10.33', '', ''],
            ['Assign', '', '1, 2, 3, 4', '9, 10, 12 (no change → converged)'],
        ]),
        $code('K-means with the elbow and silhouette methods', <<<'PY'
from sklearn.cluster import KMeans
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import silhouette_score

X_scaled = StandardScaler().fit_transform(customers[["annual_spend", "visits_per_month"]])

for k in range(2, 9):
    km = KMeans(n_clusters=k, n_init=10, random_state=42).fit(X_scaled)
    print(k, round(km.inertia_, 1), round(silhouette_score(X_scaled, km.labels_), 3))

km = KMeans(n_clusters=3, n_init=10, random_state=42).fit(X_scaled)
customers["segment"] = km.labels_
PY),
        $table('Choosing k', ['Method', 'Idea'], [
            ['Elbow', 'Plot inertia against k; pick the k where the curve bends and further clusters add little'],
            ['Silhouette score', 'For each point (b − a) / max(a, b), a = mean distance within its cluster, b = to the nearest other cluster; ranges −1 to 1, higher is better'],
            ['Business sense', 'The number of segments the team can actually act on'],
        ]),
        $table('Limitations', ['Limitation', 'Remedy'], [
            ['Must choose k in advance', 'Elbow, silhouette, gap statistic'],
            ['Assumes roughly spherical, similar-size clusters', 'Gaussian Mixture Models or DBSCAN'],
            ['Sensitive to outliers (means move)', 'Remove outliers or use K-medoids'],
            ['Sensitive to feature scale', 'Standardise features first'],
            ['Result depends on initial centroids', 'k-means++ and n_init > 1'],
        ]),
        $ask([
            ['Is K-means guaranteed to converge?', 'Yes, the objective never increases and there are finitely many assignments, so it converges, but possibly to a local minimum. Running several initialisations (n_init) and keeping the lowest inertia reduces this risk.'],
        ]),
    ]],

    ['id' => 'q16', 'title' => 'Q16. How do DBSCAN and Hierarchical Clustering work?', 'blocks' => [
        $text(
            'DBSCAN (Density-Based Spatial Clustering of Applications with Noise) groups points that are packed closely together and marks points in sparse regions as noise. It does not need k, finds clusters of any shape and handles outliers naturally.',
            'Two parameters: eps (ε), the radius of a neighbourhood, and min_samples, the minimum number of points inside that radius for a point to be dense.'
        ),
        $table('Point types in DBSCAN', ['Type', 'Rule'], [
            ['Core point', 'Has at least min_samples points (including itself) within eps'],
            ['Border point', 'Fewer than min_samples neighbours but lies within eps of a core point'],
            ['Noise point', 'Neither core nor border; label −1'],
        ]),
        $flow('DBSCAN algorithm', [
            'Pick an unvisited point and find all points within eps.',
            'If it is a core point, start a new cluster and add all its neighbours.',
            'For every neighbour that is also a core point, add its neighbours too (expand the cluster).',
            'If it is not a core point, mark it as noise for now (it may later become a border point).',
            'Repeat until every point is visited.',
        ]),
        $text('Example: delivery GPS points in a city. DBSCAN finds dense hotspots (markets, office parks) of irregular shape and labels isolated pings as noise, something K-means cannot do because it forces every point into a round cluster.'),
        $text(
            'Hierarchical clustering builds a tree of clusters (a dendrogram). Agglomerative (bottom-up) is the common form: start with every point as its own cluster and repeatedly merge the two closest clusters until one remains. Cutting the dendrogram at a chosen height gives the clusters.'
        ),
        $table('Linkage: how the distance between two clusters is measured', ['Linkage', 'Distance', 'Effect'], [
            ['Single', 'Closest pair of points', 'Long chain-like clusters; sensitive to noise'],
            ['Complete', 'Farthest pair of points', 'Compact clusters'],
            ['Average', 'Mean of all pairwise distances', 'Balanced'],
            ['Ward', 'Increase in within-cluster variance', 'Similar-size compact clusters; most common default'],
        ]),
        $code('DBSCAN and agglomerative clustering', <<<'PY'
from sklearn.cluster import DBSCAN, AgglomerativeClustering
from sklearn.neighbors import NearestNeighbors
from scipy.cluster.hierarchy import linkage, dendrogram
import numpy as np

# Pick eps from the k-distance plot: sort each point's distance to its 5th neighbour and look for the knee
distances, _ = NearestNeighbors(n_neighbors=5).fit(X).kneighbors(X)
k_dist = np.sort(distances[:, -1])

db = DBSCAN(eps=0.5, min_samples=5).fit(X)
print(set(db.labels_))            # -1 = noise

agg = AgglomerativeClustering(n_clusters=4, linkage="ward").fit(X)
Z = linkage(X, method="ward")      # dendrogram(Z) draws the merge tree
PY),
        $table('K-means vs DBSCAN vs Hierarchical', ['Feature', 'K-means', 'DBSCAN', 'Hierarchical'], [
            ['Needs number of clusters', 'Yes', 'No', 'No (cut the tree later)'],
            ['Cluster shape', 'Spherical', 'Any shape', 'Depends on linkage'],
            ['Handles outliers', 'Poorly', 'Yes (noise label)', 'Poorly with single linkage'],
            ['Scales to large n', 'Very well', 'Well with spatial index', 'Poorly: O(n²) memory'],
            ['Varying density', 'n/a', 'Struggles (use HDBSCAN)', 'Reasonable'],
        ]),
        $ask([
            ['When would you choose hierarchical clustering?', 'Small to medium datasets where you want to see the structure at several levels, e.g. grouping products into categories and sub-categories, or genes by expression, and the dendrogram itself is useful.'],
        ]),
    ]],

    ['id' => 'q17', 'title' => 'Q17. What is PCA and why do we reduce dimensions?', 'blocks' => [
        $text(
            'Principal Component Analysis (PCA) transforms correlated features into a smaller set of new uncorrelated features called principal components. The first component points in the direction of maximum variance in the data, the second in the direction of maximum remaining variance perpendicular to the first, and so on.',
            'Why reduce dimensions: the curse of dimensionality (distances become less meaningful, data becomes sparse), faster training, less overfitting, removing multicollinearity, and visualising high-dimensional data in 2-D or 3-D.',
            'Example: a house dataset has carpet area, built-up area, number of rooms and number of bathrooms, all strongly correlated. PCA may combine them into one "size" component that keeps most of the information.'
        ),
        $flow('PCA step by step', [
            'Standardise every feature (mean 0, standard deviation 1); PCA is driven by variance, so scale matters.',
            'Compute the covariance matrix of the features (d × d).',
            'Compute its eigenvectors (directions) and eigenvalues (variance along each direction).',
            'Sort components by eigenvalue, largest first.',
            'Keep the top k components that explain enough variance (e.g. 95%).',
            'Project the data onto those k components: Z = X · Wₖ.',
        ]),
        $formula('Key formulas', <<<'TXT'
Covariance matrix:      C = (1 / (n − 1)) · Xᵀ X          (X standardised)
Eigen decomposition:    C · v = λ · v
Explained variance of component i = λᵢ / Σ λⱼ
Projection:             Z = X · W_k     (W_k = first k eigenvectors as columns)
TXT),
        $code('PCA in scikit-learn', <<<'PY'
from sklearn.decomposition import PCA
from sklearn.preprocessing import StandardScaler
from sklearn.pipeline import make_pipeline
import numpy as np

X_scaled = StandardScaler().fit_transform(X)
pca = PCA().fit(X_scaled)
cumulative = np.cumsum(pca.explained_variance_ratio_)
k = int(np.argmax(cumulative >= 0.95)) + 1
print(f"{k} components keep 95% of the variance")

pipe = make_pipeline(StandardScaler(), PCA(n_components=0.95), LogisticRegression(max_iter=1000))
PY),
        $table('Other dimensionality reduction methods', ['Method', 'Type', 'Use'], [
            ['PCA', 'Linear, unsupervised', 'Compression, decorrelation, preprocessing'],
            ['LDA', 'Linear, supervised', 'Maximises class separation'],
            ['t-SNE', 'Non-linear', '2-D visualisation of clusters; not for feature input'],
            ['UMAP', 'Non-linear', 'Faster visualisation that preserves more global structure'],
            ['Autoencoders', 'Non-linear, neural', 'Learned compression, anomaly detection'],
            ['Feature selection (Lasso, mutual information)', 'Keeps original features', 'When interpretability of features matters'],
        ]),
        $ask([
            ['What is the main drawback of PCA?', 'Components are combinations of all original features, so they are hard to explain to the business ("component 1 = 0.4·area + 0.3·rooms …"). It also only captures linear structure and ignores the target.'],
        ]),
    ]],

    ['id' => 'q18', 'title' => 'Q18. How do Gradient Boosting and XGBoost work?', 'blocks' => [
        $text(
            'Gradient Boosting builds an ensemble of shallow decision trees one after another. Each new tree is trained to predict the residual errors (more generally, the negative gradient of the loss) of the ensemble built so far, and its prediction is added with a small weight, the learning rate.',
            'Example: predicting house prices. Tree 1 predicts the average price, ₹60 lakh, for a house actually worth ₹75 lakh, leaving a residual of +15. Tree 2 learns that houses in this locality are under-predicted and adds +10 × 0.1. Each tree corrects a little more of the remaining error.'
        ),
        $flow('Gradient boosting for regression', [
            'Start with a constant prediction F₀(x) = mean(y).',
            'Compute residuals rᵢ = yᵢ − F(xᵢ) (the negative gradient of squared error).',
            'Fit a small tree hₘ(x) to the residuals.',
            'Update: Fₘ(x) = Fₘ₋₁(x) + η · hₘ(x).',
            'Repeat for M trees, or stop early when the validation loss stops improving.',
        ]),
        $table('Residuals shrinking (η = 0.5)', ['House', 'Actual', 'F₀', 'Residual 1', 'F₁', 'Residual 2'], [
            ['A', 50, 60, -10, 55, -5], ['B', 70, 60, 10, 65, 5], ['C', 60, 60, 0, 60, 0],
        ]),
        $text(
            'XGBoost (Extreme Gradient Boosting) is an optimised implementation with: second-order gradients (Newton steps); L1 and L2 regularisation on leaf weights in the objective; built-in handling of missing values (learns the best direction for missing data at each split); column and row subsampling; parallel split finding with histogram methods; and tree pruning based on gain.',
            'LightGBM grows trees leaf-wise with histogram binning (very fast on large data); CatBoost handles categorical features natively with ordered target statistics.'
        ),
        $formula('XGBoost objective and split gain', <<<'TXT'
Obj = Σ l(yᵢ, ŷᵢ) + Σ Ω(fₖ),   Ω(f) = γ·T + ½·λ·Σ wⱼ²     T = leaves, w = leaf weights

Optimal leaf weight:  wⱼ* = − Gⱼ / (Hⱼ + λ)         G = sum of gradients, H = sum of hessians
Gain = ½ [ G_L²/(H_L+λ) + G_R²/(H_R+λ) − (G_L+G_R)²/(H_L+H_R+λ) ] − γ
TXT),
        $code('XGBoost with early stopping', <<<'PY'
from xgboost import XGBClassifier

model = XGBClassifier(
    n_estimators=2000, learning_rate=0.05, max_depth=5,
    subsample=0.8, colsample_bytree=0.8, reg_lambda=1.0,
    eval_metric="auc", early_stopping_rounds=50,
)
model.fit(X_train, y_train, eval_set=[(X_valid, y_valid)], verbose=False)
print(model.best_iteration)
importance = model.get_booster().get_score(importance_type="gain")
PY),
        $table('Important hyperparameters', ['Parameter', 'Effect'], [
            ['learning_rate (eta)', 'Smaller = more trees needed but better generalisation (0.01–0.1)'],
            ['n_estimators + early stopping', 'Number of trees; let early stopping choose'],
            ['max_depth', 'Tree complexity (3–8 typical)'],
            ['subsample, colsample_bytree', 'Row/column sampling to reduce overfitting'],
            ['min_child_weight, gamma', 'Minimum hessian per leaf and minimum gain to split'],
            ['reg_alpha, reg_lambda', 'L1 and L2 regularisation on leaf weights'],
        ]),
        $ask([
            ['Random Forest vs Gradient Boosting?', 'Random Forest trains deep trees independently in parallel and averages them (reduces variance; few hyperparameters, hard to overfit). Gradient Boosting trains shallow trees sequentially to reduce bias; usually more accurate on tabular data but needs tuning and is more sensitive to noise.'],
        ]),
    ]],

    ['id' => 'q19', 'title' => 'Q19. How do Neural Networks and Backpropagation work?', 'blocks' => [
        $text(
            'A neural network is layers of neurons. Each neuron computes a weighted sum of its inputs plus a bias and passes it through a non-linear activation function. Stacking layers lets the network learn complex non-linear relationships: early layers learn simple features, later layers combine them.',
            'Without non-linear activations, any number of layers collapses into a single linear transformation, so activations are essential.'
        ),
        $formula('One layer', <<<'TXT'
z = W · x + b          W = weight matrix, b = bias vector
a = g(z)               g = activation function

ReLU(z)    = max(0, z)                 hidden layers (default)
Sigmoid(z) = 1 / (1 + e^(−z))          binary output probability
Softmax(zᵢ)= e^(zᵢ) / Σⱼ e^(zⱼ)          multi-class probabilities
Tanh(z)    = (e^z − e^(−z)) / (e^z + e^(−z))
TXT),
        $flow('Training: forward pass, loss, backpropagation, update', [
            'Forward pass: compute each layer\'s output from inputs to prediction.',
            'Loss: compare prediction with the true label (MSE for regression, cross-entropy for classification).',
            'Backpropagation: apply the chain rule from the output layer back to the input layer to get ∂Loss/∂W for every weight.',
            'Update: an optimiser (SGD, Adam) moves every weight against its gradient.',
            'Repeat over mini-batches and epochs.',
        ]),
        $formula('Chain rule for one weight', <<<'TXT'
∂L/∂w = ∂L/∂a · ∂a/∂z · ∂z/∂w

Output layer with sigmoid + binary cross-entropy:  ∂L/∂z = a − y
Hidden layer:  δˡ = (Wˡ⁺¹)ᵀ · δˡ⁺¹ ⊙ g'(zˡ),    ∂L/∂Wˡ = δˡ · (aˡ⁻¹)ᵀ
TXT),
        $code('A small network in PyTorch', <<<'PY'
import torch
from torch import nn

model = nn.Sequential(
    nn.Linear(20, 64), nn.ReLU(), nn.Dropout(0.2),
    nn.Linear(64, 32), nn.ReLU(),
    nn.Linear(32, 1),                 # logits; sigmoid is inside the loss
)
loss_fn = nn.BCEWithLogitsLoss()
optimizer = torch.optim.Adam(model.parameters(), lr=1e-3)

for epoch in range(20):
    for xb, yb in train_loader:
        optimizer.zero_grad()
        loss = loss_fn(model(xb).squeeze(1), yb.float())
        loss.backward()               # backpropagation computes all gradients
        optimizer.step()              # gradient descent update
PY),
        $table('Deep learning architectures', ['Architecture', 'Idea', 'Typical use'], [
            ['MLP (fully connected)', 'Every neuron connected to every input', 'Tabular data, small problems'],
            ['CNN', 'Convolution filters share weights across positions', 'Images, video, some audio'],
            ['RNN / LSTM / GRU', 'Hidden state carried across time steps; gates control memory', 'Older sequence models, time series'],
            ['Transformer', 'Self-attention relates every token to every other token in parallel', 'Language models (GPT, BERT), vision, speech'],
            ['Autoencoder', 'Compress to a bottleneck and reconstruct', 'Anomaly detection, denoising'],
            ['GAN / Diffusion', 'Generate new data', 'Image generation'],
        ]),
        $table('Training techniques', ['Technique', 'Purpose'], [
            ['He / Xavier initialisation', 'Keep signal variance stable across layers'],
            ['Batch / Layer normalisation', 'Stabilise and speed up training'],
            ['Dropout', 'Regularisation by randomly dropping neurons'],
            ['Residual (skip) connections', 'Let gradients flow through very deep networks'],
            ['Learning-rate schedules, early stopping', 'Converge well without overfitting'],
            ['Transfer learning', 'Start from a pretrained model and fine-tune on small data'],
        ]),
        $ask([
            ['When would you not use deep learning?', 'Small tabular datasets, when interpretability is required, or with tight compute/latency budgets. Gradient boosting usually beats neural networks on typical tabular business data.'],
            ['Why ReLU instead of sigmoid in hidden layers?', 'Sigmoid saturates (gradient near 0 for large |z|), causing vanishing gradients. ReLU has gradient 1 for positive inputs, is cheap to compute and trains faster. Leaky ReLU or GELU avoid "dead" neurons.'],
        ]),
    ]],

    ['id' => 'q20', 'title' => 'Q20. Which evaluation metrics do you use: Precision, Recall, F1, ROC-AUC, PR-AUC?', 'blocks' => [
        $text(
            'Accuracy alone misleads on imbalanced data. If 1% of transactions are fraud, a model that always says "not fraud" is 99% accurate and completely useless. The confusion matrix shows exactly what kind of mistakes a classifier makes.'
        ),
        $table('Confusion matrix (fraud model, 1,000 transactions)', ['', 'Predicted fraud', 'Predicted genuine'], [
            ['Actually fraud (20)', 'TP = 15', 'FN = 5'],
            ['Actually genuine (980)', 'FP = 30', 'TN = 950'],
        ]),
        $formula('Metrics from this matrix', <<<'TXT'
Accuracy  = (TP + TN) / total        = (15 + 950) / 1000 = 0.965
Precision = TP / (TP + FP)           = 15 / 45  = 0.333   of flagged transactions, how many are fraud
Recall    = TP / (TP + FN)           = 15 / 20  = 0.750   of all fraud, how much we caught (sensitivity, TPR)
Specificity = TN / (TN + FP)         = 950 / 980 = 0.969
F1        = 2 · P · R / (P + R)      = 2·0.333·0.75 / 1.083 = 0.462
FPR       = FP / (FP + TN)           = 30 / 980 = 0.031
TXT),
        $table('Which metric matters', ['Situation', 'Prioritise', 'Why'], [
            ['Cancer screening, fraud detection', 'Recall', 'Missing a positive (FN) is very costly'],
            ['Spam filter, legal document flagging', 'Precision', 'A false alarm (FP) is costly: important mail lost'],
            ['Both errors matter, imbalanced data', 'F1 or Fβ', 'Balances precision and recall (β > 1 favours recall)'],
            ['Ranking quality across thresholds', 'ROC-AUC', 'Probability a random positive is scored above a random negative'],
            ['Heavily imbalanced, positives matter', 'PR-AUC (average precision)', 'Not inflated by the large number of true negatives'],
            ['Regression', 'MAE, RMSE, MAPE, R²', 'MAE robust to outliers; RMSE penalises large errors'],
        ]),
        $text(
            'ROC curve: plot True Positive Rate (recall) against False Positive Rate as the decision threshold moves from 1 to 0. AUC = 1 is perfect, 0.5 is random guessing. PR curve: plot precision against recall; its baseline equals the positive rate (0.02 here), so it shows weaknesses that ROC-AUC hides on rare-event problems.',
            'Threshold tuning: models output probabilities; the default 0.5 threshold is rarely optimal. Choose the threshold from the PR curve using business costs, e.g. "flag when probability > 0.2 because a missed fraud costs ₹50,000 and a manual review costs ₹200".'
        ),
        $code('Computing metrics and choosing a threshold', <<<'PY'
from sklearn.metrics import (confusion_matrix, classification_report, roc_auc_score,
                             average_precision_score, precision_recall_curve)
import numpy as np

proba = model.predict_proba(X_test)[:, 1]
pred = (proba >= 0.5).astype(int)

print(confusion_matrix(y_test, pred))
print(classification_report(y_test, pred, digits=3))
print("ROC-AUC", roc_auc_score(y_test, proba), "PR-AUC", average_precision_score(y_test, proba))

precision, recall, thresholds = precision_recall_curve(y_test, proba)
f1 = 2 * precision * recall / (precision + recall + 1e-12)
best = thresholds[np.argmax(f1[:-1])]
print("threshold with best F1:", round(best, 3))
PY),
        $table('Multi-class averaging', ['Average', 'How', 'Use'], [
            ['Macro', 'Mean of per-class scores', 'Every class equally important'],
            ['Weighted', 'Mean weighted by class support', 'Reflects class frequencies'],
            ['Micro', 'Pool all TP/FP/FN, then compute', 'Equals accuracy for single-label problems'],
        ]),
        $ask([
            ['ROC-AUC is 0.95 but the business says the model is useless. Why?', 'With extreme imbalance, a low FPR still means many false positives in absolute numbers, so precision is poor. Check PR-AUC and precision at the operating threshold, and evaluate with business cost rather than AUC alone.'],
            ['What is log loss and why is it used?', 'Log loss (cross-entropy) = −mean[y·log(p) + (1−y)·log(1−p)]. It rewards well-calibrated probabilities and heavily penalises confident wrong predictions; it is the loss logistic regression and neural classifiers optimise.'],
        ]),
    ]],
];

return [
    'ml-interview-part-3' => [
        'title' => 'ML Interview Part 3: Gradient Descent, Bias–Variance, Validation, Clustering, PCA, XGBoost, Neural Networks & Metrics',
        'view' => 'learning.sql-case-studies',
        'summary' => 'Gradient descent and optimisers, bias–variance, overfitting, cross-validation and tuning, K-means, DBSCAN and hierarchical clustering, PCA, gradient boosting and XGBoost, neural networks and backpropagation, and classification metrics, with formulas, worked numbers and Python code.',
        'example_label' => 'Running examples: salary prediction, customer segments, house prices and fraud detection',
        'questions' => array_column($sections, 'title'),
        'sections' => $sections,
    ],
];
