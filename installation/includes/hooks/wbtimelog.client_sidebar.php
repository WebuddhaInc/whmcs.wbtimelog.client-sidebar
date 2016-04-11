<?php

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

add_hook('ClientAreaPrimarySidebar', 2, function ($primarySidebar)
	{

		// Configuration
			$config = array(
				'purchase_credits_uri'  => 'cart.php',
				'show_credit_breakdown' => true
				);
			$language = array(
				'panel_title'             => 'Credit Activity',
				'total_hours'             => '<b>%s</b> total hours logged',
				'total_credits'           => '<b>%s</b> total credits purchased',
				'total_credits_expired'   => '<b>%s</b> credits expired',
				'total_credits_purchased' => '<b>%s</b> credits purchased',
				'total_balance'           => '<b>%s</b> hours support remaining'
				);

		// Stage Client
	    $client = Menu::context('client');
	    $clientId = $client->id;

    // Validate
	    if( !$clientId )
	    	return;

    // Query Data
	    $dbh =& wbDatabase::getInstance();
	    $dbh->runQuery("
	      SELECT
	      	(
		      	SELECT SUM(`log`.`hours`)
		      	FROM `tbladdon_wbclientstimelog` AS `log`
		      	WHERE `log`.`userid` = '$clientId'
	      	) AS `total_hours`
	        ,
	        (
		        SELECT SUM(`credit`.`hours`)
		        FROM `tbladdon_wbclientstimelog_credits` AS `credit`
		        WHERE `credit`.`userid` = '$clientId'
		        	AND `credit`.`hours` > 0
	        ) AS `total_credits_purchased`
	        ,
	        (
		        SELECT SUM(`credit`.`hours`)
		        FROM `tbladdon_wbclientstimelog_credits` AS `credit`
		        WHERE `credit`.`userid` = '$clientId'
		        	AND `credit`.`hours` < 0
	        ) AS `total_credits_expired`
	        ");

    // Record
	    $record = $dbh->getRow();
			$record['total_credits'] = ($record['total_credits_purchased'] + $record['total_credits_expired']);
			$record['total_balance'] = $record['total_credits'] - $record['total_hours'];

		// Report
			$report['total_hours']             = round($record['total_hours'],2);
			$report['total_credits_purchased'] = round($record['total_credits_purchased'],2);
			$report['total_credits_expired']   = abs(round($record['total_credits_expired'],2));
			$report['total_hours']             = ($record['total_hours'] >= 0 ? round($record['total_hours'],2) : '<font color=red>('.abs(round($record['total_hours'],2)).')</font>');
			$report['total_credits']           = ($record['total_credits'] >= 0 ? round($record['total_credits'],2) : '<font color=red>('.abs(round($record['total_credits'],2)).')</font>');
			$report['total_balance']           = ($record['total_balance'] >= 0 ? round($record['total_balance'],2) : '<font color=red>('.abs(round($record['total_balance'],2)).')</font>');

    // Build Sidebar
			$childPosition = 1;
			$newSidebar = $primarySidebar->addChild(
					'wbtimelog_sidebar_module',
					array(
							'label' => 'Credit Activity',
			        'icon'  => 'fa-clock-o',
							'footerHtml' => '<a href="'. $config['purchase_credits_uri'] .'" class="btn btn-success btn-sm btn-block"><i class="fa fa-repeat"></i> Purchase Hours</a>'
					)
			);
			$newSidebar->moveToFront();
			$newSidebar->moveDown();
			$newSidebar->addChild(
			    'total_hours',
			    array(
			    		'label' => sprintf($language['total_hours'], $report['total_hours']),
			        'icon'  => 'fa-arrow-circle-o-down fa-fw',
			        'order' => $childPosition ++,
			    )
			);
			if ($config['show_credit_breakdown'])
			{
				$newSidebar->addChild(
						'total_credits_purchased',
				    array(
				    		'label' => sprintf($language['total_credits_purchased'], $report['total_credits_purchased']),
				        'icon'  => 'fa-arrow-circle-o-up fa-fw',
				        'order' => $childPosition ++,
				    )
				);
				$newSidebar->addChild(
						'total_credits_expired',
				    array(
				    		'label' => sprintf($language['total_credits_expired'], $report['total_credits_expired']),
				        'icon'  => 'fa-times-circle-o fa-fw',
				        'order' => $childPosition ++,
				    )
				);
			}
			else
			{
				$newSidebar->addChild(
						'total_credits',
				    array(
				    		'label' => sprintf($language['total_credits'], $report['total_credits']),
				        'icon'  => 'fa-arrow-circle-o-up fa-fw',
				        'order' => $childPosition ++,
				    )
				);
			}
			$newSidebar->addChild(
					'total_balance',
			    array(
			    		'label' => sprintf($language['total_balance'], $report['total_balance']),
			        'icon'  => 'fa-check-circle fa-fw',
			        'order' => $childPosition ++,
			    )
			);

	}
);
