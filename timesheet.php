<?php
/*
 * _GET: start_microtime
* _GET: stop_microtime
* _GET: project
*/
ini_set('session.gc_maxlifetime', 24*60*60); //24 hours
session_start();

$timer=new timer();
$timer->init();
$timer->load();

$project=$timer->get_name('project');
if($project!=null)
	$timer->set_project($project);

$action=$timer->get_name('action');
switch($action)
{
	case 'start':
		$timer->start();
		break;
	case 'stop':
		$timer->stop();
		break;
}

$timer->list_projects();
$timer->list_timesheet();

//
class timer
{
	public $timesheet=null;

	public $start_microtime=null;
	public $stop_microtime=null;
	public $project=null;

	public function init()
	{
		if(isset($_SESSION['project']))
			$this->project=$_SESSION['project'];

		//
		if(!isset($_SESSION['start_microtime']))
			$_SESSION['start_microtime']=null;
		if(!isset($_SESSION['stop_microtime']))
			$_SESSION['stop_microtime']=null;

		//
		$project=$this->get_name('project');
		if($project!=null)
			$this->set_project($project);

		//
		if($this->project==null and count($this->timesheet)==1)
		{
			$this->project=$this->timesheet[0];
		}
	}

	public function set_project($project)
	{
		$this->project=$project;
		$_SESSION['project']=$project;
	}

	public function get_url()
	{
		$s='timesheet.php?s=';
		if($this->project!=null) $s.='&project='.$this->project;
		if($this->start_microtime!=null) $s.='&start_microtime='.$this->start_microtime;
		if($this->stop_microtime!=null) $s.='&stop_microtime='.$this->stop_microtime;
		return $s;
	}

	public function get_number($name)
	{
		if(isset($_GET[$name]))
		{
			$s=$_GET[$name];
			$s=preg_replace("/[^0-9.+-]/","",$s);
			if (!is_numeric($s)) return null;
			return $s;
		}
		return null;
	}

	public function get_name($name)
	{
		if(isset($_GET[$name]))
		{
			$s=$_GET[$name];
			$s=preg_replace("/[^A-Za-z0-9.+-]/","",urldecode($s));
			if($s=='') return null;
			return $s;
		}
		return null;
	}

	public function start()
	{
		$this->project=$_SESSION['project'];
		$this->start_microtime=microtime(true);
		$_SESSION['start_microtime']=$this->start_microtime;
	}

	public function stop()
	{
		$this->project=$_SESSION['project'];

		if(isset($_SESSION['start_microtime']) and $_SESSION['start_microtime']!=null)
			$this->start_microtime=$_SESSION['start_microtime'];

		$start_microtime=$this->get_name('start_microtime');
		if($start_microtime!=null)
			$this->start_microtime=$start_microtime;

		$this->stop_microtime=microtime(true);
		$this->timesheet[$this->project][]=array(
				'start'=>$this->start_microtime,
				'stop'=>$this->stop_microtime
		);
		self::save();

		self::clear_timer();
	}

	public function clear_timer()
	{
		$this->start_microtime=null;
		$this->stop_microtime=null;
		$_SESSION['start_microtime']=null;
		$_SESSION['stop_microtime']=null;
	}

	public function load()
	{
		$pathname="timesheet.array.php";

		if(!file_exists($pathname))
		{
			$timesheet=array(
					'default'=>array()
			);
		}
		else
			include $pathname;
		$this->timesheet=$timesheet;
		//		print_r($this->timesheet);
	}

	public function save()
	{
		$s="<"."?"."php \$timesheet = ".var_export($this->timesheet,true).";"."?".">\r\n\r\n";

		$pathname="timesheet.array.php";

		copy($pathname,"/home/lasellers/$pathname.".time());

		if(!$file_handle = fopen($pathname,"w"))
			die("Can not open file $pathname for writing (0x".base_convert(fileperms($pathname),10,8).").");
		else
		{
			$bytes=strlen($s);
			if(!fwrite($file_handle, $s))
				die("Can not write $bytes bytes to $pathname.");
			fclose($file_handle);
		}
	}

	public function list_projects()
	{
		echo "<fieldset><legend>Projects</legend>";
		$url=self::get_url();
		foreach($this->timesheet as $project=>$a)
		{
			echo "<a href=\"$url&project=$project\">$project</a><br>";
		}
		echo "</fieldset>";
	}

	public function list_timesheet()
	{
		if(isset($_SESSION['project']) and $_SESSION['project']!=null)
		{
			$this->project=$_SESSION['project'];
			$start_microtime=$_SESSION['start_microtime'];

			$total_t=0;
			echo "<fieldset><legend>Timesheet for ".$this->project."</legend>";
			$url=self::get_url();
			if($start_microtime==null)
				echo "<a class=button href=\"$url&action=start\">Start</a> ";
			else
				echo "<a class=button href=\"$url&action=stop\">Stop</a> ";
			echo "<hr>";
			echo '<table>';
			foreach($this->timesheet[$this->project] as $key=>$a)
			{
				$start=$a['start'];
				$stop=$a['stop'];
				$t=$stop-$start;
				echo "<tr><td>$key</td><td>$start</td><td>$stop</td><td>$t</td><td><b>".$this->print_hours($t)."</b></td></tr>";
				$total_t+=$t;
			}
			echo '</table>';
			$dt=self::print_hours($total_t);
			echo "Total: $total_t s or <b>$dt</b><br>";
			echo "</fieldset>";
		}
	}


	public function add_project()
	{

	}

	public function delete_project()
	{

	}


	public function print_hours($microtime)
	{
		$t=(int)($microtime);

		$s=$t%60;
		$t=$t/60;

		$m=$t%60;
		$t=$t/60;

		$h=$t;

		$str=(int)($h)." hours ".(int)($m)." minutes ".(int)($s)." seconds";
		return $str;
	}

	public function load_config()
	{
		$pathname="config.array.php";

		if(!file_exists($pathname))
		{
			$config = array (
					'' =>
					array (
							'user'=>'timesheet',
							'backup_path' => '/home/'
					)
			);
		}
		else
			include $pathname;
		$this->config=$config;
		//		print_r($this->timesheet);
	}

	public function save_config()
	{
		$s="<"."?"."php \$config = ".var_export($this->config,true).";"."?".">\r\n\r\n";

		$pathname="config.array.php";

		if(!$file_handle = fopen($pathname,"w"))
			die("Can not open file $pathname for writing (0x".base_convert(fileperms($pathname),10,8).").");
		else
		{
			$bytes=strlen($s);
			if(!fwrite($file_handle, $s))
				die("Can not write $bytes bytes to $pathname.");
			fclose($file_handle);
		}
	}


}
?>