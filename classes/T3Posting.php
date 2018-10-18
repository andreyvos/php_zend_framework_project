<?

/*

   Цель класса T3Posting:

   А) В класс передается лид,

   Б) Затем генерируется PingTree, Как это устроено?

      1) У нас есть претенденты - все кто сообтветствуют фильтрам
         Надо выяснить какие критерии могут являться фильтрами

      2) Для всех участников прошедших фильтр расчитывается вес (Наверное вес будет целым числом)
        Вес расчитывается по 2ум критериям:
          - Реальные критерии. Например: цена, скорость ответа...
          - Ручной Коеффициент, которые умножается или сумируется с реальным коеффициентом

      3) Затем если вес целочесленный, то мы создаем список в котором колличество повторений участника равно значению его веса.

      4) Затем мы начинаем выбирать эллементы со случайным индексом, и строить цепочку - PingTree
        Эта цепь следует правилу - чем выше вес, тем больше вероятность что будешь первым.

   В) Затем начинается отправка лида лендеру
        - У отправки есть единый интерфейс, но под каждого лендера делается свой класс отправки данных и получения и парсинга ответа
        - Во время каждой отправки происходит сбор статистики, такой как: время ответа, номер в цепи, ответ м т.д.
*/

class T3Posting {

    protected static $_instance;

    public $system;
    public $database;

    public $filters;

    protected function initialize() {
        $this->system = T3System::getInstance();
        $this->database = $this->system->getConnect();
    }

    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
            self::$_instance->initialize();
        }
        return self::$_instance;
    } 


    /*public function generateChain($сhannels)
    {
        $MinCoeff = 0;

        for($i = 0; $i <count($сhannels); $i++)
        {
          //$сhannels[$i]->CalculateCoeff();

          if ($i == 0)
          {
              // Присваиваем минимальное значение
              $MinCoeff = $сhannels[$i]->Coeff;
          }
          else if ($сhannels[$i]->Coeff < $MinCoeff)
          {
              $MinCoeff = $сhannels[$i]->Coeff;
          }
        }

        $Chain = array();

        // Теперь приводоим все коеффициенты к целому числу, и создаем цепь в которой id канала повоторяется $C раз
        for($i = 0; $i <count($сhannels); $i++)
        {
             $C =  round($сhannels[$i]->Coeff / $MinCoeff);
             $сhannels[$i]->Coeff = $C;

             for ($j = 0; $j < $C; $j++)
             {
                $Chain[] = $сhannels[$i]->id;
             }
        }

        $ResultChain = array();

        for($i = 0; $i <count($сhannels); $i++)
        {
            $index = rand(0, count($Chain) - 1);
            $CurrentId = $Chain[$index];
            // Добавляем
            $ResultChain[$i]->id = $CurrentId;

            $NewChain = array();
            for ($j = 0; $j < count($Chain); $j++)
            {
                //echo ($Chain[$j]).", ";
                if ($Chain[$j] != $CurrentId)
                {
                    $NewChain[] = $Chain[$j];
                }
            }
            //echo "\r\n";
            $Chain = $NewChain;
        }



        return $ResultChain;
    }
    */
}


