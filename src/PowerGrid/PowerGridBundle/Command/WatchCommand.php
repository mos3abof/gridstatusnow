<?php

namespace PowerGrid\PowerGridBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

use PowerGrid\PowerGridBundle\Entity\Record;

class WatchCommand extends ContainerAwareCommand
{
    protected $em;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('power:watch')
            ->setDescription('Watch the status of the power and save it in the DB')
            ->addOption(
               'keep-going',
               'w', // w = watch
               InputOption::VALUE_NONE,
               'Check for load status every x seconds'
            )
            ->addOption(
                'period',
                NULL,
                InputOption::VALUE_REQUIRED,
                'Set the polling period in seconds (used with --keep-going)',
                30
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ( $input->getOption('keep-going') ) {
            while ( TRUE )
            {
                $this->checkStatus($input, $output);

                sleep($input->getOption('period'));
            }
        }
        else {
            $this->checkStatus($input, $output);
        }
    }

    public function checkStatus(InputInterface $input, OutputInterface $output)
    {
        try
        {
            $powerGridService = $this->getContainer()->get('power_grid_service');
            $status = $powerGridService->getStatus();

            if( $status && $status != 'Unknown' )
            {
                /** @var $em \Doctrine\ORM\EntityManager */
                $em = $this->getContainer()->get('doctrine')->getManager();

                // Keep entity manager open
                if ( ! $em->isOpen() ) {
                    $em = $em->create(
                        $em->getConnection(),
                        $em->getConfiguration()
                    );
                }

                $latestRecord = $em->getRepository('PowerGridBundle:Record')
                    ->getLatestStatus()
                    ->getQuery()
                    ->getOneOrNullResult()
                ;

                // If there is no recors or the latest record is not the same
                // Than, insert new record
                if( $latestRecord == NULL || $latestRecord->getStatus() != $status )
                {
                    $record = new Record();
                    $record->setStatus($status);
                    $em->persist($record);
                    $em->flush();

                    $output->writeln(sprintf('<info>[%s]</info> NEW STATUS (<info>%s</info>) ...', date('G:i:s'), $status));
                }
                else {
                    $output->writeln(sprintf('<info>[%s]</info> KEEP GOING, NO THING NEW ...', date('G:i:s')));
                }

                // detach all the entities to enforce loading objects from the
                // database again instead of serving them from the identity map.
                $em->clear();

            }
            else {
                $output->writeln(sprintf('<info>[%s]</info> UNKNOWN STATUS, TRY AGAIN ...', date('G:i:s')));
            }

        } catch (\Exception $e) {
            $this->getContainer()->get('doctrine')->resetManager();
            $output->writeln(sprintf('<info>[%s]</info> <error>[error]</error> %s', date('G:i:s'), $e->getMessage()));
        }
    }
}

