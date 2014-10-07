<?php
/**
 * Created by mcfedr on 05/04/2014 16:13
 */

namespace Mcfedr\AwsPushBundle\Command;

use Mcfedr\AwsPushBundle\Service\Topics;
use Mcfedr\AwsPushBundle\Topic\Topic;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListTopicArnsCommand extends Command
{
    /**
     * @var Topics
     */
    private $topics;

    /**
     * @var string
     */
    private $topicName;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Topics $topics
     * @param string $topicName
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(Topics $topics, $topicName, LoggerInterface $logger)
    {
        $this->topics = $topics;
        $this->topicName = $topicName;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('mcfedr:aws:topic-arns')
            ->setDescription('Show all the arns used for a topic')
            ->addOption('topic', null, InputOption::VALUE_REQUIRED, 'The topic name to list', $this->topicName);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var TableHelper $table */
        $table = $this->getApplication()->getHelperSet()->get('table');
        $table->setHeaders(['Number', 'Name', 'Arn']);

        $this->topics->iterateTopics(
            $input->getOption('topic'),
            function (Topic $topic) use ($table) {
                $table->addRow(
                    [
                        $topic->getNumber(),
                        $topic->getName(),
                        $topic->getArn()
                    ]
                );
            }
        );

        $table->render($output);
    }
}
