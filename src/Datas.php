<?php

namespace MyGarmin;

class Datas
{
    const CACHE_DIR = '/home/datas/Perso/Sports/GarminArchives';

    private $Garmin = null;
    private $activitiesIndex = null;
    public $activities = null;
    public $reloadForced = false;

    public function __construct($login = null, $password = null)
    {
        if (!isset($login) || !isset($password)) {
            throw new Exception("Datas: Credential missing");
        }
        
        if (!is_writable(self::CACHE_DIR) && !is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR);
        }

        if ($this->Garmin == null) {
            $this->Garmin = new GarminConnect(array(
                'username' => $login,
                'password' => $password
            ));
        }

        Log::trace($login." connected.\n");
    }

    public function getYearActivities($year)
    {
        $countActivity = 0;

        if (empty($year)) {
            $year = date('Y');
        }
        $start = 0;
        $slice = 50;
        $params = array(
         'startDate' => $year.'-01-01',
         'endDate' => $year.'-12-31',
         '_' => (new \DateTime())->getTimestamp()
        );

        do {
            $last = $start+$slice-1;
            Log::trace(" [ ".$start." ... ". $last . "] ");
            $objResults = $this->Garmin->getActivityList($start, $slice, null, $params);
            Log::trace(" (".count($objResults).") \n");
            if (count($objResults) > 0) {
                foreach ($objResults as $activity) {
                    $this->storeActivity($activity);
                    $countActivity++;
                }
            }
            if (count($objResults) == $slice) {
                $start += count($objResults);
            } else {
                $slice = 0;
            }
        } while ($slice > 0);
        Log::trace("Total of $countActivity \n");
    }

    private function storeActivity($activity)
    {
        $id    = $activity->activityId;
        
        $aData = $this->makeActivitiesDir($id).DIRECTORY_SEPARATOR.$id.".infos";

        Log::trace(sprintf("[%10.10d] ", $id));
        if (!is_file($aData) || $this->reloadForced) {
            $registered = ActivitiesIndex::store(self::CACHE_DIR, $activity);
            if ($registered) {
                $fa = fopen($aData, 'w');
                fwrite($fa, var_export($activity, true));
                fclose($fa);
                Log::trace(", GPX");
                $this->getDataFile($id, GarminConnect::DATA_TYPE_GPX);
                Log::trace(", TCX");
                $this->getDataFile($id, GarminConnect::DATA_TYPE_TCX);
                Log::trace(", FIT");
                $this->getDataFile($id, GarminConnect::DATA_TYPE_FIT);
                Log::trace(' > registered.');
            } else {
                Log::trace('**ignored**.');
            }
        } else {
            Log::trace('already stored.');
        }
        Log::trace("\n");
    }

    private function getDataFile($id, $format = GarminConnect::DATA_TYPE_GPX)
    {
        $actFile  = $this->makeActivitiesDir($id).DIRECTORY_SEPARATOR.$id.".".$format;
        $data = $this->Garmin->getDataFile($format, $id);
        $ffile = fopen($actFile, 'w');
        fwrite($ffile, $data);
        fclose($ffile);
    }

    private function makeActivitiesDir($id = 0)
    {
        $cache = self::CACHE_DIR.DIRECTORY_SEPARATOR.'activities'.DIRECTORY_SEPARATOR.$id;
        if (!is_writable($cache) && !is_dir($cache)) {
            mkdir($cache, 0777, true);
        }
        return $cache;
    }
}
