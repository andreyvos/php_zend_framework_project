<?php

class T3Leads_Arh {
    static public function arhMyltyProducts($count = 2000, $month = 3){
        // Если есть запущенная сессия по этому продукту, новая не начинается
        if(!T3Db::arh()->fetchOne("select count(*) from arc_log where `status`='run'")){
            $start = microtime(1);
            $status = 'good';  
            
            // создание новой сессии
            T3Db::arh()->insert("arc_log", array(
                'start'     => date('Y-m-d H:i:s'),
                'max_count' => $count,
                'min_month' => $month,
            ));   
            $logID = T3Db::arh()->lastInsertId();
            
            
            try{
                // ставим обе базы на транзакции
                T3Db::arh()->beginTransaction();
                T3Db::api()->beginTransaction();
                
                // получаем данные
                $allLeads = T3Db::api()->fetchAll("select * from leads_data where `datetime` < ? order by id limit {$count}", array(
                    date('Y-m-d', mktime(0, 0, 0, date('m')-$month, date('d'), date('Y')))
                ));
                
                $index = array();
                foreach($allLeads as $el){
                    if(strlen($el['product'])){ 
                        if(!isset($index[$el['product']])){
                            $index[$el['product']] = array();
                        }                              
                        $index[$el['product']][] = $el;   
                    }
                }
                
                // запись данных в архивную базу и удаление из текущей
                $logCount = count($allLeads);    
                
                if(count($index)){
                    foreach($index as $product => $allLeads){
                        $leadsIds = array();
                        foreach($allLeads as $el){
                            $leadsIds[] = $el['id'];
                        }  
                        
                        // сохранить заголовки лидов
                        T3Db::arh()->insertMulty('leads_data', array_keys($allLeads[0]), $allLeads);
                        
                        // Получить данные лидов
                        $allLeads = T3Db::api()->fetchAll("select * from leads_data_{$product} where id in (" . implode(",", $leadsIds) . ")");
                        
                        if(count($allLeads)){
                            // сохранить данные лидов
                            try {
                                T3Db::arh()->insertMulty("leads_data_{$product}", array_keys($allLeads[0]), $allLeads);    
                            }
                            catch(Exception $e){
                                if(!$e->getCode()){
                                    list($tableName, $tableCreateQuery) = array_values(T3Db::api()->fetchRow("show create table leads_data_{$product}"));
                                    
                                    // если таблица уже существует, переименовать её
                                    try{
                                        T3Db::arh()->query("rename table `leads_data_{$product}` to `leads_data_{$product}_" . date("Y_m_d_H_i_s") . "`");
                                    }
                                    catch(Exception $e){}
                                    
                                    // создать новую таблицу
                                    T3Db::arh()->query($tableCreateQuery);
                                    
                                    // добавить данные
                                    if(count($allLeads)){
                                        T3Db::arh()->insertMulty("leads_data_{$product}", array_keys($allLeads[0]), $allLeads); 
                                    } 
                                }  
                            }
                        }
                        
                        T3Db::api()->delete("leads_data", "id in (" . implode(",", $leadsIds) . ")");
                        T3Db::api()->delete("leads_data_{$product}", "id in (" . implode(",", $leadsIds) . ")");
                        
                    } 
                }   
                
                // сохранение транзакций    
                T3Db::arh()->commit(); 
                T3Db::api()->commit();
            }
            catch(Exception $e){
                //T3Db::arh()->rollBack();
                $status = 'bad';
                
                echo "Code: "       . $e->getCode()             . "<br>";
                echo "File: "       . $e->getFile()             . "<br>";
                echo "Line: "       . $e->getLine()             . "<br>";
                echo "Message: "    . $e->getMessage()          . "<br>";
                echo "Trace: "      . $e->getTraceAsString()    . "<br>";
            }
            
            // закрыть сессию
            T3Db::arh()->update("arc_log", array(
                'status'    => $status,
                'finish'    => date('Y-m-d H:i:s'),
                'runtime'   => microtime(1) - $start,
                'count'     => $logCount,
            ), "id={$logID}");
        }
    }
}