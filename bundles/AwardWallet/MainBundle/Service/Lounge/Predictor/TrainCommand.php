<?php

namespace AwardWallet\MainBundle\Service\Lounge\Predictor;

use AwardWallet\MainBundle\Entity\Lounge;
use AwardWallet\MainBundle\Service\Lounge\Logger;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;
use AwardWallet\MainBundle\Service\Lounge\MLMatcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class TrainCommand extends Command
{
    public static $defaultName = 'aw:train-lounge-predictor';

    private WeightManager $weightManager;

    private Predictor $predictor;

    private iterable $loungePredictorComparators;

    private Normalizer $normalizer;

    private Logger $logger;

    public function __construct(
        WeightManager $weightManager,
        Predictor $predictor,
        iterable $loungePredictorComparators,
        Normalizer $normalizer,
        Logger $logger
    ) {
        parent::__construct();

        $this->weightManager = $weightManager;
        $this->predictor = $predictor;
        $this->loungePredictorComparators = $loungePredictorComparators;
        $this->normalizer = $normalizer;
        $this->logger = $logger;
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Train lounge predictor')
            ->addOption('learningRate', 'l', InputOption::VALUE_REQUIRED, 'Learning rate', 0.001)
            ->addOption('lambda', 'm', InputOption::VALUE_REQUIRED, 'Regularization parameter', 0.01)
            ->addOption('batchSize', 'b', InputOption::VALUE_REQUIRED, 'Batch size', 6)
            ->addOption('epochs', 'p', InputOption::VALUE_REQUIRED, 'Number of epochs', 2000)
            ->addOption('validateWeights', 'd', InputOption::VALUE_NONE, 'Validate weights after training')
            ->addOption('earlyStop', 'o', InputOption::VALUE_REQUIRED, 'Early stopping patience', 60)
            ->addOption('validationSplit', 's', InputOption::VALUE_REQUIRED, 'Validation split ratio', 0.2)
            ->addOption('useExistingWeights', 'w', InputOption::VALUE_NONE, 'Use existing weights from DB as starting point')
            ->addOption('crossValidation', 'c', InputOption::VALUE_OPTIONAL, 'Perform k-fold cross-validation with specified k', 3)
            ->addOption('onlyCrossValidation', 'x', InputOption::VALUE_NONE, 'Only perform cross-validation without final training')
            ->addOption('skipSave', null, InputOption::VALUE_NONE, 'Skip saving weights to DB');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);
        $this->logger->info('start training lounge predictor');

        $learningRate = (float) $input->getOption('learningRate');
        $lambda = (float) $input->getOption('lambda');
        $batchSize = (int) $input->getOption('batchSize');
        $epochs = (int) $input->getOption('epochs');
        $validateWeights = (bool) $input->getOption('validateWeights');
        $earlyStop = (int) $input->getOption('earlyStop');
        $validationSplit = (float) $input->getOption('validationSplit');
        $useExistingWeights = (bool) $input->getOption('useExistingWeights');
        $kFolds = (int) $input->getOption('crossValidation');
        $onlyCrossValidation = (bool) $input->getOption('onlyCrossValidation');
        $skipSave = (bool) $input->getOption('skipSave');

        // Load the training data
        $trainingDataAll = Yaml::parseFile(__DIR__ . '/training_data.yml');
        $trainingDataAll = $trainingDataAll['training_data'];

        // Perform cross-validation if requested
        if ($kFolds > 1) {
            $output->writeln(sprintf('<info>Performing %d-fold cross-validation...</info>', $kFolds));

            // Perform cross-validation
            $cvResults = $this->performCrossValidation($input, $output, $trainingDataAll, $kFolds);

            // If only cross-validation was requested, exit
            if ($onlyCrossValidation) {
                $output->writeln('<info>Cross-validation completed. Exiting as requested.</info>');

                return 0;
            }

            $output->writeln('<info>Proceeding with full model training...</info>');
        }

        $initialWeights = null;

        // Use averaged weights from cross-validation if available
        if (isset($cvResults['averagedWeights'])) {
            $initialWeights = $cvResults['averagedWeights'];
            $output->writeln('<info>Using averaged weights from cross-validation as starting point</info>');
        }

        if ($useExistingWeights) {
            $initialWeights = $this->weightManager->getWeights();

            if ($initialWeights) {
                $this->logger->info('using existing weights from DB as starting point');
            } else {
                $this->logger->warning('no existing weights found in DB, initializing new weights');
                $initialWeights = $this->initializeWeights();
            }
        }

        // Sorting data by classes for stratified splitting
        $positiveExamples = array_filter($trainingDataAll, fn (array $example) => $example['similarity'] >= MLMatcher::getThreshold());
        $negativeExamples = array_filter($trainingDataAll, fn (array $example) => $example['similarity'] < MLMatcher::getThreshold());

        // Count of examples for validation
        $validationPositiveCount = (int) (count($positiveExamples) * $validationSplit);
        $validationNegativeCount = (int) (count($negativeExamples) * $validationSplit);

        // Splitting data while maintaining stratification
        $validationPositive = array_slice($positiveExamples, 0, $validationPositiveCount);
        $validationNegative = array_slice($negativeExamples, 0, $validationNegativeCount);
        $trainingPositive = array_slice($positiveExamples, $validationPositiveCount);
        $trainingNegative = array_slice($negativeExamples, $validationNegativeCount);

        // Union of stratified samples
        $validationData = array_merge($validationPositive, $validationNegative);
        $trainingData = array_merge($trainingPositive, $trainingNegative);

        $this->logger->info(sprintf(
            'training data size: %d (positive: %d, negative: %d), validation data size: %d (positive: %d, negative: %d)',
            count($trainingData),
            count($trainingPositive),
            count($trainingNegative),
            count($validationData),
            count($validationPositive),
            count($validationNegative)
        ));

        // Train the model using the shared method
        $result = $this->trainModel(
            $trainingData,
            $validationData,
            $initialWeights,
            $learningRate,
            $lambda,
            $batchSize,
            $epochs,
            $earlyStop
        );

        // Get best weights from training
        $weights = $result['weights'];

        $trainingTime = round(microtime(true) - $start, 2);
        $this->logger->info(sprintf('Training completed in %.2f minutes', $trainingTime / 60));

        // Display the learned weights in order of importance
        $sortedWeights = $weights;
        arsort($sortedWeights);

        $output->writeln('<info>Learned weights (sorted by importance):</info>');
        $table = new Table($output);
        $table
            ->setHeaders(['Service', 'Weight'])
            ->setRows(array_map(function ($serviceId, $weight) {
                return [
                    $serviceId,
                    sprintf('%.4f', $weight),
                ];
            }, array_keys($sortedWeights), $sortedWeights))
            ->render();

        // Save weights to database if not skipped
        if (!$skipSave) {
            $output->writeln('<info>Saving weights to database...</info>');

            if ($this->weightManager->saveWeights($weights)) {
                $output->writeln('<info>Weights saved successfully!</info>');
            } else {
                $output->writeln('<error>Failed to save weights!</error>');
            }
        } else {
            $output->writeln('<comment>Skipping weight save as requested.</comment>');
        }

        // Validate weights if requested
        if ($validateWeights) {
            $output->writeln('<info>Validating weights on training data...</info>');

            // First calculate overall metrics
            $totalMSE = 0;
            $totalMAE = 0;
            $count = 0;
            $tp = 0;
            $fp = 0;
            $tn = 0;
            $fn = 0; // For binary metrics
            $threshold = MLMatcher::getThreshold();

            // Array to hold validation results
            $validationResults = [];

            foreach ($trainingDataAll as $example) {
                /** @var LoungeInterface $lounge1 */
                $lounge1 = $this->createLounge($example['lounge1']);
                /** @var LoungeInterface $lounge2 */
                $lounge2 = $this->createLounge($example['lounge2']);
                $trueSimilarity = $example['similarity'];

                $predicted = $this->predictor->predict($lounge1, $lounge2, $weights);
                $error = $predicted - $trueSimilarity;

                // Calculate metrics
                $totalMSE += pow($error, 2);
                $totalMAE += abs($error);
                $count++;

                // Binary classification metrics
                if ($trueSimilarity >= $threshold && $predicted >= $threshold) {
                    $tp++;
                }

                if ($trueSimilarity < $threshold && $predicted >= $threshold) {
                    $fp++;
                }

                if ($trueSimilarity < $threshold && $predicted < $threshold) {
                    $tn++;
                }

                if ($trueSimilarity >= $threshold && $predicted < $threshold) {
                    $fn++;
                }

                // Store result for detailed table
                $validationResults[] = [
                    'lounge1' => $lounge1,
                    'lounge2' => $lounge2,
                    'true' => $trueSimilarity,
                    'predicted' => $predicted,
                    'error' => abs($error),
                ];
            }

            // Calculate summary metrics
            $mse = $totalMSE / $count;
            $mae = $totalMAE / $count;
            $accuracy = ($tp + $tn) / $count;
            $precision = ($tp + $fp) > 0 ? $tp / ($tp + $fp) : 0;
            $recall = ($tp + $fn) > 0 ? $tp / ($tp + $fn) : 0;
            $f1 = ($precision + $recall) > 0 ? 2 * ($precision * $recall) / ($precision + $recall) : 0;

            // Display summary metrics
            $output->writeln(sprintf('<info>Model Performance Summary:</info>'));
            $output->writeln(sprintf('Total examples: %d', $count));
            $output->writeln(sprintf('MSE: %.4f (mean squared error)', $mse));
            $output->writeln(sprintf('MAE: %.4f (mean absolute error)', $mae));
            $output->writeln(sprintf('RMSE: %.4f (root mean squared error)', sqrt($mse)));
            $output->writeln(sprintf('Accuracy: %.2f%% (true positive + true negative)', $accuracy * 100));
            $output->writeln(sprintf('Precision: %.2f%% (true positive / (true positive + false positive))', $precision * 100));
            $output->writeln(sprintf('Recall: %.2f%% (true positive / (true positive + false negative))', $recall * 100));
            $output->writeln(sprintf('F1 Score: %.2f%% (2 * (precision * recall) / (precision + recall))', $f1 * 100));

            // Sort results by error for easier inspection
            usort($validationResults, function ($a, $b) {
                return $b['error'] <=> $a['error']; // Sort by largest error first
            });

            // Create table with the top N errors
            $n = min(500, count($validationResults));
            $output->writeln(sprintf('<info>Top %d examples with largest error:</info>', $n));

            $table = new Table($output);
            $table->setHeaders(['Lounge 1', 'Lounge 2', 'True', 'Predicted', 'Abs Error']);

            for ($i = 0; $i < $n; $i++) {
                $result = $validationResults[$i];
                $table->addRow([
                    $this->lounge2string($result['lounge1']),
                    $this->lounge2string($result['lounge2']),
                    sprintf('%.2f', $result['true']),
                    sprintf('%.2f', $result['predicted']),
                    sprintf('%.2f', $result['error']),
                ]);
            }

            $table->render();
        }

        return 0;
    }

    private function trainModel(
        array $trainingData,
        array $validationData,
        ?array $initialWeights,
        float $learningRate,
        float $lambda,
        int $batchSize,
        int $epochs,
        int $earlyStop
    ): array {
        $transformer = new PolynomialFeatureTransformer(2, true);

        // Initialize weights if not provided
        $weights = $initialWeights ?? $this->initializeWeights();

        // Parameters for early stopping
        $bestValidationLoss = PHP_FLOAT_MAX;
        $bestWeights = $weights;
        $patienceLeft = $earlyStop;
        $noImprovementCount = 0;

        for ($epoch = 0; $epoch < $epochs; $epoch++) {
            shuffle($trainingData);

            $epochLoss = 0;
            $batchCount = 0;

            // Process in batches
            foreach (array_chunk($trainingData, $batchSize) as $batch) {
                $gradients = array_fill_keys(array_keys($weights), 0);
                $batchLoss = 0;

                foreach ($batch as $example) {
                    /** @var LoungeInterface $lounge1 */
                    $lounge1 = $this->createLounge($example['lounge1']);
                    /** @var LoungeInterface $lounge2 */
                    $lounge2 = $this->createLounge($example['lounge2']);
                    $trueSimilarity = $example['similarity'];

                    // normalize lounges
                    $lounge1Normalized = $this->normalizer->normalize($lounge1);
                    $lounge2Normalized = $this->normalizer->normalize($lounge2);

                    $predicted = $this->predictor->predict($lounge1, $lounge2, $weights);
                    $error = $predicted - $trueSimilarity;
                    $batchLoss += pow($error, 2); // Squared error

                    // Calculate base feature similarities
                    $baseFeatures = [];

                    foreach ($this->loungePredictorComparators as $comparator) {
                        $featureId = get_class($comparator);
                        $featureValue = $comparator->compare($lounge1Normalized, $lounge2Normalized);
                        $baseFeatures[$featureId] = max(0, min(1, $featureValue));
                    }

                    // Apply polynomial transformation
                    $transformedFeatures = $transformer->transform($baseFeatures);

                    // Calculate gradients for all features
                    foreach ($transformedFeatures as $featureId => $value) {
                        if (isset($gradients[$featureId])) {
                            $gradients[$featureId] += $error * $value;
                        }
                    }
                }

                // Update weights with L2 regularization
                foreach ($weights as $featureId => $weight) {
                    $weights[$featureId] -= $learningRate * (
                        ($gradients[$featureId] / count($batch)) + ($lambda * $weight)
                    );
                }

                $epochLoss += $batchLoss / count($batch);
                $batchCount++;
            }

            $trainingLoss = $epochLoss / $batchCount;

            // Calculate validation loss
            $validationLoss = 0;

            foreach ($validationData as $example) {
                /** @var LoungeInterface $lounge1 */
                $lounge1 = $this->createLounge($example['lounge1']);
                /** @var LoungeInterface $lounge2 */
                $lounge2 = $this->createLounge($example['lounge2']);
                $trueSimilarity = $example['similarity'];
                $predicted = $this->predictor->predict($lounge1, $lounge2, $weights);
                $validationLoss += pow($predicted - $trueSimilarity, 2);
            }

            $validationLoss /= count($validationData); // Mean squared error

            // Early stopping check
            if ($validationLoss < $bestValidationLoss) {
                $bestValidationLoss = $validationLoss;
                $bestWeights = $weights;
                $patienceLeft = $earlyStop; // Reset patience
                $noImprovementCount = 0;
            } else {
                $patienceLeft--;
                $noImprovementCount++;
            }

            // Log training progress periodically
            if ($epoch % max(1, intval($epochs / 20)) === 0) {
                $message = sprintf(
                    'Epoch %d/%d: training MSE = %.6f, validation MSE = %.6f, no improvement: %d',
                    $epoch + 1,
                    $epochs,
                    $trainingLoss,
                    $validationLoss,
                    $noImprovementCount
                );

                $this->logger->info($message);
            }

            // Check if we should stop early
            if ($patienceLeft <= 0) {
                $message = sprintf(
                    'Early stopping at epoch %d. Best validation MSE: %.6f',
                    $epoch + 1,
                    $bestValidationLoss
                );

                $this->logger->info($message);

                break;
            }
        }

        // Return best weights and metrics
        $metrics = $this->evaluateModel($bestWeights, $validationData);

        return [
            'weights' => $bestWeights,
            'metrics' => $metrics,
        ];
    }

    private function initializeWeights(): array
    {
        $weights = [];
        $comparators = it($this->loungePredictorComparators)
            ->reindex(fn ($comparator) => get_class($comparator))
            ->toArrayWithKeys();

        // Create a transformer to generate polynomial features
        $transformer = new PolynomialFeatureTransformer(2, true);

        // Generate a sample of base features with values 0.5 to simulate average comparator output
        $sampleFeatures = array_fill_keys(array_keys($comparators), 0.5);

        // Transform to get all possible feature IDs that will be created
        $transformedSample = $transformer->transform($sampleFeatures);

        // Count the number of features for He initialization
        $n = count($transformedSample);

        // He initialization: scale ~ sqrt(2/n) for better convergence
        $scale = sqrt(2.0 / $n);

        // Initialize all with He initialization
        foreach (array_keys($transformedSample) as $featureId) {
            // Generate random number from normal distribution
            $u1 = (float) mt_rand() / (float) mt_getrandmax();
            $u2 = (float) mt_rand() / (float) mt_getrandmax();

            // Box-Muller transform to get normal distribution
            if ($u1 < 1e-7) {
                $u1 = 1e-7;
            } // Prevent log(0)
            $z = sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);

            // Scale by He initialization factor
            $weights[$featureId] = $z * $scale;

            // Original features should have higher initial weights
            if (strpos($featureId, 'original_') === 0) {
                $weights[$featureId] *= 2.0; // Give more importance to original features initially
            }
        }

        $this->logger->info('Initialized weights with He initialization for polynomial features', [
            'total_weights' => count($weights),
            'base_features' => count($comparators),
            'polynomial_features' => count($weights) - count($comparators),
            'scale' => $scale,
        ]);

        return $weights;
    }

    /**
     * @return array Statistics from cross-validation (metrics averages and standard deviations)
     */
    private function performCrossValidation(InputInterface $input, OutputInterface $output, array $trainingData, int $kFolds = 5): array
    {
        // Get training parameters
        $learningRate = (float) $input->getOption('learningRate');
        $lambda = (float) $input->getOption('lambda');
        $batchSize = (int) $input->getOption('batchSize');
        $epochs = (int) $input->getOption('epochs');
        $earlyStop = (int) $input->getOption('earlyStop');

        // Metrics to collect
        $metrics = [
            'mse' => [],
            'mae' => [],
            'rmse' => [],
            'accuracy' => [],
            'precision' => [],
            'recall' => [],
            'f1' => [],
            'auc' => [],
        ];

        // Separate data by similarity for stratification
        $positiveSamples = array_filter($trainingData, fn (array $example) => $example['similarity'] >= MLMatcher::getThreshold());
        $negativeSamples = array_filter($trainingData, fn (array $example) => $example['similarity'] < MLMatcher::getThreshold());

        // Shuffle the data
        shuffle($positiveSamples);
        shuffle($negativeSamples);

        // Create folds with stratification
        $positiveChunks = array_chunk($positiveSamples, (int) ceil(count($positiveSamples) / $kFolds));
        $negativeChunks = array_chunk($negativeSamples, (int) ceil(count($negativeSamples) / $kFolds));

        // Ensure we have exactly k folds
        while (count($positiveChunks) < $kFolds) {
            $positiveChunks[] = [];
        }

        while (count($negativeChunks) < $kFolds) {
            $negativeChunks[] = [];
        }

        $folds = [];
        $allFoldsWeights = [];

        for ($i = 0; $i < $kFolds; $i++) {
            $folds[$i] = array_merge($positiveChunks[$i], $negativeChunks[$i]);
        }

        // For each fold as validation set
        for ($foldIndex = 0; $foldIndex < $kFolds; $foldIndex++) {
            $output->writeln(sprintf('<info>Fold %d/%d</info>', $foldIndex + 1, $kFolds));

            // Prepare validation and training datasets
            $validationFold = $folds[$foldIndex];
            $trainingFolds = [];

            for ($i = 0; $i < $kFolds; $i++) {
                if ($i !== $foldIndex) {
                    $trainingFolds = array_merge($trainingFolds, $folds[$i]);
                }
            }

            // Train model for this fold
            $result = $this->trainModel(
                $trainingFolds,
                $validationFold,
                null, // Start with new weights for each fold
                $learningRate,
                $lambda,
                $batchSize,
                $epochs,
                $earlyStop
            );

            $foldMetrics = $result['metrics'];
            $allFoldsWeights[] = $result['weights'];

            // Add this fold's metrics to our collection
            foreach ($foldMetrics as $metricName => $value) {
                $metrics[$metricName][] = $value;
            }

            $output->writeln(sprintf(
                '<info>Fold %d results: MSE=%.4f, Accuracy=%.2f%%, F1=%.2f%%</info>',
                $foldIndex + 1,
                $foldMetrics['mse'],
                $foldMetrics['accuracy'] * 100,
                $foldMetrics['f1'] * 100
            ));
        }

        // Calculate statistics (mean and std) for each metric
        $results = [];

        foreach ($metrics as $metricName => $values) {
            if (empty($values)) {
                continue;
            }

            $mean = array_sum($values) / count($values);

            // Calculate standard deviation
            $variance = 0;

            foreach ($values as $value) {
                $variance += pow($value - $mean, 2);
            }

            $std = sqrt($variance / count($values));

            $results[$metricName] = [
                'mean' => $mean,
                'std' => $std,
                'values' => $values,
            ];
        }

        // Print summary statistics
        $output->writeln('<info>Cross-validation summary:</info>');
        $table = new Table($output);
        $table->setHeaders(['Metric', 'Mean', 'Std', 'Min', 'Max']);

        $rows = [];

        foreach ($results as $metricName => $stats) {
            if ($metricName === 'values') {
                continue;
            }

            $mean = $stats['mean'];
            $std = $stats['std'];
            $min = min($stats['values']);
            $max = max($stats['values']);

            // Format percentages for appropriate metrics
            if (in_array($metricName, ['accuracy', 'precision', 'recall', 'f1'])) {
                $rows[] = [
                    ucfirst($metricName),
                    sprintf('%.2f%%', $mean * 100),
                    sprintf('±%.2f%%', $std * 100),
                    sprintf('%.2f%%', $min * 100),
                    sprintf('%.2f%%', $max * 100),
                ];
            } else {
                $rows[] = [
                    ucfirst($metricName),
                    sprintf('%.4f', $mean),
                    sprintf('±%.4f', $std),
                    sprintf('%.4f', $min),
                    sprintf('%.4f', $max),
                ];
            }
        }

        $table->setRows($rows);
        $table->render();

        $averagedWeights = [];
        $allFeatureIds = [];

        foreach ($allFoldsWeights as $foldWeights) {
            foreach (array_keys($foldWeights) as $featureId) {
                $allFeatureIds[$featureId] = true;
            }
        }

        foreach (array_keys($allFeatureIds) as $featureId) {
            $sum = 0;
            $count = 0;

            foreach ($allFoldsWeights as $foldWeights) {
                if (isset($foldWeights[$featureId])) {
                    $sum += $foldWeights[$featureId];
                    $count++;
                }
            }

            $averagedWeights[$featureId] = $sum / $count;
        }

        $results['averagedWeights'] = $averagedWeights;

        return $results;
    }

    /**
     * Evaluates model performance on a dataset.
     *
     * @param array $weights Model weights
     * @param array $dataset Evaluation dataset
     * @return array Metrics including MSE, MAE, accuracy, precision, recall, and F1
     */
    private function evaluateModel(array $weights, array $dataset): array
    {
        $totalMSE = 0;
        $totalMAE = 0;
        $count = 0;
        $tp = 0;
        $fp = 0;
        $tn = 0;
        $fn = 0;
        $threshold = MLMatcher::getThreshold();

        $predictions = [];
        $trueLabels = [];

        foreach ($dataset as $example) {
            /** @var LoungeInterface $lounge1 */
            $lounge1 = $this->createLounge($example['lounge1']);
            /** @var LoungeInterface $lounge2 */
            $lounge2 = $this->createLounge($example['lounge2']);
            $trueSimilarity = $example['similarity'];

            $predicted = $this->predictor->predict($lounge1, $lounge2, $weights);
            $error = $predicted - $trueSimilarity;

            // Calculate metrics
            $totalMSE += pow($error, 2);
            $totalMAE += abs($error);
            $count++;

            // Store for AUC calculation
            $predictions[] = $predicted;
            $trueLabels[] = $trueSimilarity >= $threshold ? 1 : 0;

            // Binary classification metrics
            if ($trueSimilarity >= $threshold && $predicted >= $threshold) {
                $tp++;
            } elseif ($trueSimilarity < $threshold && $predicted >= $threshold) {
                $fp++;
            } elseif ($trueSimilarity < $threshold && $predicted < $threshold) {
                $tn++;
            } elseif ($trueSimilarity >= $threshold && $predicted < $threshold) {
                $fn++;
            }
        }

        // Calculate overall metrics
        $mse = $totalMSE / $count;
        $mae = $totalMAE / $count;
        $rmse = sqrt($mse);
        $accuracy = ($tp + $tn) / $count;
        $precision = ($tp + $fp) > 0 ? $tp / ($tp + $fp) : 0;
        $recall = ($tp + $fn) > 0 ? $tp / ($tp + $fn) : 0;
        $f1 = ($precision + $recall) > 0 ? 2 * ($precision * $recall) / ($precision + $recall) : 0;

        // Calculate AUC
        $auc = $this->calculateAUC($predictions, $trueLabels);

        return [
            'mse' => $mse,
            'mae' => $mae,
            'rmse' => $rmse,
            'accuracy' => $accuracy,
            'precision' => $precision,
            'recall' => $recall,
            'f1' => $f1,
            'auc' => $auc,
        ];
    }

    /**
     * Calculates Area Under the ROC Curve (AUC).
     *
     * @param array $predictions Predicted values
     * @param array $trueLabels True binary labels (0/1)
     * @return float AUC value
     */
    private function calculateAUC(array $predictions, array $trueLabels): float
    {
        // Sort predictions and keep track of true labels
        $combined = [];

        for ($i = 0; $i < count($predictions); $i++) {
            $combined[] = [
                'prediction' => $predictions[$i],
                'label' => $trueLabels[$i],
            ];
        }

        // Sort by prediction in descending order
        usort($combined, function ($a, $b) {
            return $b['prediction'] <=> $a['prediction'];
        });

        // Count positive and negative examples
        $positiveCount = array_sum($trueLabels);
        $negativeCount = count($trueLabels) - $positiveCount;

        if ($positiveCount == 0 || $negativeCount == 0) {
            return 0.5; // Can't calculate AUC with only one class
        }

        // Calculate AUC using the trapezoidal rule
        $truePositiveRate = 0;
        $falsePositiveRate = 0;
        $previousTruePositiveRate = 0;
        $previousFalsePositiveRate = 0;
        $auc = 0;

        $truePositives = 0;
        $falsePositives = 0;

        foreach ($combined as $item) {
            if ($item['label'] == 1) {
                $truePositives++;
            } else {
                $falsePositives++;
            }

            $truePositiveRate = $truePositives / $positiveCount;
            $falsePositiveRate = $falsePositives / $negativeCount;

            // Trapezoid area
            $auc += ($falsePositiveRate - $previousFalsePositiveRate) *
                ($truePositiveRate + $previousTruePositiveRate) / 2;

            $previousTruePositiveRate = $truePositiveRate;
            $previousFalsePositiveRate = $falsePositiveRate;
        }

        return $auc;
    }

    private function lounge2string(LoungeInterface $lounge): string
    {
        $terminal = $lounge->getTerminal();
        $gate1 = $lounge->getGate();
        $gate2 = $lounge->getGate2();
        $range = array_unique(array_filter([$gate1, $gate2]));

        return sprintf(
            '[%s] "%s", %s, %s',
            $lounge->getAirportCode(),
            $lounge->getName(),
            $terminal ? sprintf('"%s"', $terminal) : '<NULL>',
            count($range) > 0 ? sprintf('"%s"', implode(', ', $range)) : '<NULL>'
        );
    }

    private function createLounge(array $config): Lounge
    {
        return (new Lounge())
            ->setName($config['name'])
            ->setAirportCode('LAX')
            ->setTerminal($config['terminal'] ?? null)
            ->setGate($config['gate'] ?? null)
            ->setGate2($config['gate2'] ?? null);
    }
}
