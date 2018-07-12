<?php

namespace Bex\Behat\StepTimeLoggerExtension\Listener;

use Behat\Behat\Definition\Search\SearchEngine;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeStepTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use Bex\Behat\StepTimeLoggerExtension\ServiceContainer\Config;
use Bex\Behat\StepTimeLoggerExtension\Service\StepTimeLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class StepTimeLoggerListener implements EventSubscriberInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var StepTimeLogger
     */
    private $stepTimeLogger;

    /**
     * @var SearchEngine
     */
    private $searchEngine;

    /**
     * @param Config         $config
     * @param StepTimeLogger $stepTimeLogger
     */
    public function __construct(Config $config, StepTimeLogger $stepTimeLogger, SearchEngine $searchEngine)
    {
        $this->config = $config;
        $this->stepTimeLogger = $stepTimeLogger;
        $this->searchEngine = $searchEngine;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            StepTested::BEFORE => 'stepStarted',
            StepTested::AFTER => 'stepFinished',
            ExerciseCompleted::AFTER => 'suiteFinished'
        ];
    }

    /**
     * @param BeforeStepTested $event
     */
    public function stepStarted(BeforeStepTested $event)
    {
        if ($this->config->isEnabled()) {
            $definition = $this->searchEngine->searchDefinition($event->getEnvironment(), $event->getFeature(), $event->getStep());

            $this->stepTimeLogger->logStepStarted($definition->getMatchedDefinition()->getPath());
        }
    }

    /**
     * @param AfterStepTested $event
     */
    public function stepFinished(AfterStepTested $event)
    {
        if ($this->config->isEnabled()) {
            $definition = $this->searchEngine->searchDefinition($event->getEnvironment(), $event->getFeature(), $event->getStep());

            $this->stepTimeLogger->logStepFinished($definition->getMatchedDefinition()->getPath());
        }
    }

    /**
     * @return void
     */
    public function suiteFinished()
    {
        if ($this->config->isEnabled()) {
            $calledCounts = $this->stepTimeLogger->getCalledCounts();
            $avgTimes = $this->stepTimeLogger->getAvegrageExecutionTimes();
            $this->stepTimeLogger->clearLogs();

            foreach ($this->config->getOutputPrinters() as $printer) {
                $printer->printLogs($calledCounts, $avgTimes);
            }
        }
    }
}
