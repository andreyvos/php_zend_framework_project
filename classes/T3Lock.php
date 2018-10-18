<?php
class T3Lock
{
    
    public static function lock($name,$custom_data=null)
    {
        $bind = array
                                    (
                                        'name'=>$name,
                                        'status'=>'working',
                                        'start_time'=>time(),
                                        
                                    );


        if(!is_null($custom_data))
        {
                $bind['custom_data'] = serialize($custom_data);
        }
        $id =  T3Db::api()->select()->from('locks','id')->where("name=?",$name)->query()->fetchColumn();
        if($id)
        {
            $bind["end_time"] = null;
            T3Db::api()->update('locks',$bind,"name='$name'");
        }
        else
        {
            T3Db::api()->insert('locks',$bind);
        
        }
    }
    public static function unlock($name,$custom_data=null)
    {
        $bind =  array(
                                            'status'=>'finished',
                                            'end_time'=>time(),
                                            
        );
        if(!is_null($custom_data))
        {
            $bind['custom_data']= serialize($custom_data);
        }
        T3Db::api()->update('locks',$bind,"name='{$name}' and status='working'");
    }
    public static function isLocked($name)
    {
        
        
        $id =  T3Db::api()->select()->from('locks','id')->where("name=?",$name)->where("status=?","working")->query()->fetchColumn();
        if($id)
        {
            return true;
        }
        return false; 
    }
    
}

