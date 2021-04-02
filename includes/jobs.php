<?php
require_once('../config.php');
require_once('../veeam.class.php');

session_start();
error_reporting(E_ALL || E_STRICT);

$veeam = new VBO($host, $port, $version);

if (isset($_SESSION['token'])) {
	$veeam->setToken($_SESSION['token']);
    $user = $_SESSION['user'];
	$jobs = $veeam->getJobs();
	$org = $veeam->getOrganizations();
?>
<div class="main-container">
    <h1>Jobs</h1>
    <?php
    if (count($jobs) !== 0) {
	?>
	<input class="form-control search" id="filter-jobs" placeholder="Filter jobs..." />
    <table class="table table-hover table-bordered table-striped table-border" id="table-jobs">
        <thead>
            <tr>
                <th>Job Name</th>
				<th>Organization</th>
                <th>Status</th>
				<th>Next Run</th>
                <th>Description</th>
                <th class="text-center">Schedule</th>
                <th class="text-center">Restore Points</th>
                <th class="text-center">Options</th>
            </tr>
        </thead>
        <tbody>
		<?php
		for ($i = 0; $i < count($jobs); $i++) {
			echo '<tr>';
			echo '<td>' . $jobs[$i]['name'] . '</td>';
			
			$id = explode('/', $jobs[$i]['_links']['organization']['href']); // Get the organization ID
			
			for ($j = 0; $j < count($org); $j++) {
				if ($org[$j]['id'] === end($id)) {
					echo '<td>' . $org[$j]['name'] . '</td>';
				}
			}

			echo '<td>' . (isset($jobs[$i]['lastRun']) ? $jobs[$i]['lastStatus'] . ' (' .  date('d/m/Y H:i T', strtotime($jobs[$i]['lastRun'])) . ')' : $jobs[$i]['lastStatus']) . '</td>';
			echo '<td>' . (isset($jobs[$i]['nextRun']) ? date('d/m/Y H:i T', strtotime($jobs[$i]['nextRun'])) : 'Not scheduled') . '</td>';
			echo '<td>' . $jobs[$i]['description'] . '</td>';
			echo '<td class="pointer text-center" data-toggle="collapse" data-target="#schedule'.$i.'"><a href="#" onclick="return false;">View</a></td>';
			echo '<td class="pointer text-center" data-toggle="collapse" data-target="#restorepoints'.$i.'"><a href="#" onclick="return false;">View</a></td>';
			echo '<td>';
			
			if ($jobs[$i]['isEnabled'] != '1' || empty($jobs[$i]['isEnabled'])) {
				echo '<button class="btn btn-default btn-change-job-state" id="btn-change-job-state-' . $jobs[$i]['id'] . '" data-call="enable" data-name="' . $jobs[$i]['name'] . '" data-jid="' . $jobs[$i]['id'] . '" title="Change state"><i class="fa fa-power-off text-success fa-lg btn-state-' . $jobs[$i]['id'] . '"></i></button></a>';
			} else {
				echo '<button class="btn btn-default btn-change-job-state" id="btn-change-job-state-' . $jobs[$i]['id'] . '" data-call="disable" data-name="' . $jobs[$i]['name'] . '" data-jid="' . $jobs[$i]['id'] . '" title="Change state"><i class="fa fa-power-off text-danger fa-lg btn-state-' . $jobs[$i]['id'] . '"></i></button></a>';
			}
			
			echo '&nbsp;<button class="btn btn-success btn-job-start" data-name="' . $jobs[$i]['name'] . '" data-jid="' . $jobs[$i]['id'] . '" title="Start job"><i class="fa fa-play"></i></button></a>';
			echo '</td>';
			echo '</tr>';
			
			echo '<tr>';
			echo '<td colspan="7" class="zeroPadding">';
			echo '<div id="schedule'.$i.'" class="accordian-body collapse">';
			?>
				<table class="table table-bordered table-small table-striped">
					<thead>
						<tr>
							<th>Schedule Policy</th>
							<th>Periodically Run</th>
							<th>Daily Type</th>
							<th>Run At</th>
							<th class="text-center">Retry Enabled</th>
							<th>Retry Number</th>
							<th>Retry Wait Interval</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<?php
							echo '<td>' . $jobs[$i]['schedulePolicy']['type'] . '</td>';
							echo '<td>' . (isset($jobs[$i]['schedulePolicy']['periodicallyEvery']) ? $jobs[$i]['schedulePolicy']['periodicallyEvery'] : 'N/A') . '</td>';
							echo '<td>' . (isset($jobs[$i]['schedulePolicy']['dailyType']) ? $jobs[$i]['schedulePolicy']['dailyType'] : 'N/A') . '</td>';
							echo '<td>' . (isset($jobs[$i]['schedulePolicy']['dailyTime']) ? $jobs[$i]['schedulePolicy']['dailyTime'] : 'N/A') . '</td>';
							echo '<td class="text-center">';
							if ($jobs[$i]['schedulePolicy']['retryEnabled'] == 'true') { echo '<span class="label label-success">Yes</span>'; } else { echo '<span class="label label-danger">No</span>'; }
							echo '</td>';
							echo '<td>' . (isset($jobs[$i]['schedulePolicy']['retryNumber']) ? $jobs[$i]['schedulePolicy']['retryNumber'] : 'N/A') . '</td>';
							echo '<td>' . (isset($jobs[$i]['schedulePolicy']['retryWaitInterval']) ? $jobs[$i]['schedulePolicy']['retryWaitInterval'] . 'm' : 'N/A') . '</td>';
							?>
						</tr>
					</tbody>
				</table>
			<?php
			echo '</div>';
			echo '</td>';
			echo '</tr>';
			
			echo '<tr>';
			echo '<td colspan="7" class="zeroPadding">';
			echo '<div id="restorepoints'.$i.'" class="accordian-body collapse">';
			?>
				<table class="table table-bordered table-small table-striped">
				<thead>
				<tr>
				<th>Point In Time</th>
				<th>Status</th>
				<th>Bottleneck</th>
				<th>Transferred</th>
				<th class="text-center">Session Log</th>
				</tr>
				</thead>
				<tbody>
				<?php
				$jobsession = $veeam->getJobSession($jobs[$i]['id']);
				
				if ($version === 'v5') {
					$jobsession = $jobsession['results'];
				}
				
				for ($j = 0; $j < count($jobsession); $j++) {
					echo '<tr>';
					echo '<td>' . (isset($jobsession[$j]['endTime']) ? date('d/m/Y H:i T', strtotime($jobsession[$j]['endTime'])) : 'N/A') . '</td>';
					if (strcmp($jobsession[$j]['status'], 'Success') === 0) {
						echo '<td><span class="label label-success">' . $jobsession[$j]['status'] . '</span></td>';    
					} else if (strcmp($jobsession[$j]['status'], 'Warning') === 0) {
						echo '<td><span class="label label-warning">' . $jobsession[$j]['status'] . '</span></td>';    
					} else {
						echo '<td><span class="label label-danger">' . $jobsession[$j]['status'] . '</span></td>';    
					}
					echo '<td>' . $jobsession[$j]['statistics']['bottleneck'] . '</td>';
					echo '<td>' . $jobsession[$j]['statistics']['processedObjects'] . ' items processed</td>';
					echo '<td class="text-center"><a href="#" class="item" data-sessionid="' . $jobsession[$j]['id'] . '" data-sessiontime="' . (isset($jobsession[$j]['endTime']) ? date('d/m/Y H:i T', strtotime($jobsession[$j]['endTime'])) : 'N/A') . '" onclick="return false;">View</a></td>';					
					echo '</tr>';
				}
				?>
				</tbody>
				</table>
			<?php
			echo '</div>';
			echo '</td>';
			echo '</tr>';
		}
		?>
        </tbody>
    </table>
    <?php
    } else {
        echo '<p>No backup jobs have been configured.</p>';
    }
    ?>
</div>

<div class="modal" id="sessionModalCenter" role="dialog">
    <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title" id="session-title">Session info</h1>
      </div>
      <div class="modal-body">
        <table class="table table-bordered table-padding table-striped" id="table-session-content">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Duration</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
      </div>
	  <div class="modal-footer">
        <button type="button" class="btn btn-success" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
$('#filter-jobs').keyup(function(e) {
    var searchText = $(this).val().toLowerCase();
    
    $.each($('#table-jobs tbody tr'), function(e) {
        if ($(this).text().toLowerCase().indexOf(searchText) === -1) {
           $(this).hide();
        } else {
           $(this).show();
        }
    });
});

$('.item').click(function(e) {
    var icon, text;
    var id = $(this).data('sessionid');
    var time = $(this).data('sessiontime');
	
	$('#session-title').text('Session info (' + time + ')');
	
    $.post('veeam.php', {'action' : 'getbackupsessionlog', 'id' : id}).done(function(data) {
        response = JSON.parse(data);

        $('#table-session-content tbody').empty();
        
        for (var i = 0; i < response.results.length; i++) {
            if (response.results[i].title.match(/Success/g)) {
                icon = 'check-circle';
                text = 'success';
            } else if (response.results[i].title.match(/Warning/g)) {
                icon = 'exclamation-triangle';
                text = 'warning';
            } else {
                icon = 'times-circle';
                text = 'danger';
            }
			
			var creationTime = moment(response.results[i].creationTime);
			var endTime = moment(response.results[i].endTime);
			var duration = moment.duration(endTime.diff(creationTime));
			
            $('#table-session-content tbody').append('<tr> \
                    <td><span class="fa fa-'+icon+' text-'+text+'" title="'+text.charAt(0).toUpperCase() + text.slice(1) +'"></span> ' + response.results[i].title + '</td> \
                    <td>' + moment.utc(duration.asMilliseconds()).format('HH:mm:ss') + '</td> \
                    </tr>');
        }
        
        $('#sessionModalCenter').modal('show');
    });
});

$('.btn-change-job-state').click(function(e) {
    var jid = $(this).data('jid');
    var name = $(this).data('name');
    var call = $(this).data('call');
    var json = '{ "'+call+'": null }';

    $.post('veeam.php', {'action' : 'changejobstate', 'id' : jid, 'json' : json}).done(function(data) {
		if (call === 'enable') {
			$('#btn-change-job-state-'+jid).data('call', 'disable');
			$('.btn-state-'+jid).removeClass('text-success');
			$('.btn-state-'+jid).addClass('text-danger');
		} else {			
			$('#btn-change-job-state-'+jid).data('call', 'enable');
			$('.btn-state-'+jid).addClass('text-success');
			$('.btn-state-'+jid).removeClass('text-danger');
		}
		
		Swal.fire({
			icon: 'info',
			title: 'Job state',
			text: 'Job "' + name + '" has been ' + call + 'd'
		})
	});
});

$('.btn-job-start').click(function(e) {
    var id = $(this).data('jid');
    var name = $(this).data('name');
    
    $.post('veeam.php', {'action' : 'startjob', 'id' : id}).done(function(data) {
		var response = JSON.parse(data);

		if (response == null) {
			var message = 'Job has been started';
		} else {
			var message = response['message'].slice(0, -1);
		}
		
		Swal.fire({
			icon: 'info',
			title: 'Job status',
			text: '' + message
		})
    });
});
</script>
<?php
} else {
	if (isset($_SESSION['refreshtoken'])) {
		$veeam->refreshToken($_SESSION['refreshtoken']);
		
		$_SESSION['refreshtoken'] = $veeam->getRefreshToken();
        $_SESSION['token'] = $veeam->getToken();
	} else {
		$veeam->logout();
		?>
		<script>
		Swal.fire({
			icon: 'info',
			title: 'Session expired',
			html: 'Your session has expired and requires you to log in again',
		}).then(function(e) {
			window.location.href = '/';
		});
		</script>
		<?php
	}
}
?>