<?php


$dst_date = new DateTime('2014-09-26 00:00');
print $dst_date->format('d-m-Y h:i:s') . "\n";



$sql = 'SELECT * FROM status WHERE corrected = 0 ORDER BY id ASC';

$mydb = new mysqli("localhost", "root", "serverPASSWD12", "power-grid");

if($records = $mydb->query($sql))
{
	while($record = $records->fetch_object())
	{
		$record_date = new DateTime($record->timestamp);
		$record_date->modify('+3 hour');

		if(
			(intval($record_date->format('m')) < intval($dst_date->format('m'))) ||
			( (intval($record_date->format('m')) == intval($dst_date->format('m'))) && (intval($record_date->format('d')) < intval($dst_date->format('d'))) )
		)
		{

			$record_updated_date = new DateTime($record->timestamp);
			$record_updated_date->modify('+3 hour');

			$sql = 'UPDATE status SET corrected = 1, `timestamp` = \'' . $record_updated_date->format('Y-m-d H:i:s') . '\' WHERE id = ' . $record->id;
			// print $sql;
			// exit;

			if(!$mydb->query($sql))
			{
				print 'Problem with record id ' . $record->id . "\n";
			}
			else
			{
				print 'UPDATED record id ' . $record->id . ' successfully' . "\n";
			}
		}
		else
		{
			$record_updated_date = new DateTime($record->timestamp);
			$record_updated_date->modify('+2 hour');

			$sql = 'UPDATE status SET corrected = 1, `timestamp` = \'' . $record_updated_date->format('Y-m-d H:i:s') . '\' WHERE id = ' . $record->id;

			if(!$mydb->query($sql))
			{
				print 'Problem with record id ' . $record->id . "\n";
			}
			else
			{
				print '[DST] UPDATED record id ' . $record->id . ' successfully' . "\n";
			}
		}
	}
}

