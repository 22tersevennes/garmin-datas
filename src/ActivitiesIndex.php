<?php

namespace MyGarmin;

class ActivitiesIndex
{
    public function store($baseDir, $activity)
    {
        if (!is_dir($baseDir) || !is_writable($baseDir)) {
            throw new Exception("ActivitiesIndex: can't access $baseDir");
        }

        self::array2key($colsDatas, "", get_object_vars($activity));
        
        //echo "SQL > \n";
        //echo "- 8< ----------------------\n$sqlInsert\n - >8 -------------------------\n";
        $indexFile = $baseDir.DIRECTORY_SEPARATOR."activities.sqlite";
        if (!is_file($indexFile)) {
            $sqlCreate = "CREATE TABLE activities ( id INT PRIMARY KEY";
            foreach ($colsDatas as $k => $v) {
                $sqlCreate .= ", " . $k . " STRING";
            }
            $sqlCreate .= ")";
            $indexDB = new \SQLite3($indexFile);
            $indexDB->exec($sqlCreate);
        } else {
            $indexDB = new \SQLite3($indexFile, SQLITE3_OPEN_READWRITE);
        }

        $tableDesc = $indexDB->query('PRAGMA table_info(activities);');
        $validColums = array();
        while ($cols = $tableDesc->fetchArray(SQLITE3_NUM)) {
            $validColums[$cols[1]] = $cols[2];
        }
        $sqlInsert = "INSERT OR REPLACE INTO activities ( id";
        $values = "";
        foreach ($colsDatas as $k => $v) {
            if (isset($validColums[$k])) {
                $sqlInsert .= ", " . $k . "";
                $values .= ", \"" .$v . "\"";
            } else {
                Log::error("** [".$activity->activityId."] Missing column $k in activities table\n");
            }
        }
        $sqlInsert .= ")";
        $sqlInsert .= " VALUES ( ".$activity->activityId . $values . ")";
        $indexDB->exec($sqlInsert);
        $indexDB->close();
        return true;
    }


    public function getActivity($id)
    {
        $return = array();
        $sql = sprintf('SELECT * FROM activities WHERE id=%d', $id);
        $results = $this->indexDB->query($sql);
        while ($row = $results->fetchArray()) {
            $return = array_merge($results, $row);
        }
        return $results;
    }

    private function array2key(&$cols, $label, $suba)
    {
        foreach ($suba as $k => $v) {
            $clabel = $label.($label!=""?"_":"").$k;
            if (is_array($v) || is_object($v)) {
                $vo = self::object2array($v);
                self::array2key($cols, $clabel.($clabel!=""?"_":"").$k, $vo);
            } else {
                $cols[$clabel] = $v;
            }
        }
    }

    private function object2array($data)
    {
        if (is_array($data) || is_object($data)) {
            $result = array();
            foreach ($data as $key => $value) {
                $result[$key] = self::object2array($value);
            }
            return $result;
        }
        return $data;
    }
}
