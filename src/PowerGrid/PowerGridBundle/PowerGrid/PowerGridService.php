<?php

namespace PowerGrid\PowerGridBundle\PowerGrid;

class PowerGridService
{
    private $source_url = 'http://loadmeter.egyptera.org/MiniCurrentLoadClock3.aspx';

    public function getStatus()
    {
        try
        {
            if ( ($content = @file_get_contents($this->source_url)) == FALSE ) {
                throw new \Exception("Source seems to be down", 1);
            }

            if( stripos($content, 'images/c3.gif') !== FALSE ) {
                return 'Danger';
            }
            elseif( stripos($content, 'images/c2.gif') !== FALSE ) {
                return 'Warning';
            }
            elseif( stripos($content, 'images/c1.gif') !== FALSE ) {
                return 'Safe';
            }
        }
        catch(\Exception $e) {
            return 'Unknown';
        }
    }
}
