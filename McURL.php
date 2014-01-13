<?php
/**
 * Multi cURL wrapper
 * 
 * @author Oleg Savvateev <o.savvateev@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */


class McURL {

    /**
     * @var int Кол-во потоков.
     */
    private $_threads = 10;

    /**
     * @var array Очередь запросов.
     */
    protected $_queue = array();

    /**
     * Добавление запроса в очередь.
     * @param string $url URL запроса.
     * @param string $callback Имя сallback функции, которая выполняется после завершения запроса.
     * @param string $proxy HTTP-прокси, через который будет направляться запрос.
     * @param string $browser Браузер (эквивалент CURLOPT_USERAGENT для сURL).
     * @param array $curlOpt Дополнительные опции для cURL, если необходимо (http://php.net/manual/ru/function.curl-setopt.php).
     * @param mixed $data Дополнительные данные, привязанные к запросу (доступны в callback функции через $data).
     */
    public function add($url, $callback=null, $proxy=null, $browser=null, $curlOpt=null, $data=null)
    {
        $this->_queue[] = array($url, $callback, $proxy, $browser, $curlOpt, $data);
    }

    /**
     * Запуск обработки запросов в установленное кол-во потоков.
     */
    public function run()
    {
        $startTime = microtime(true);
        $mh = curl_multi_init();
        $hData = array();
        $activeThreads = 0;
        while(!empty($this->_queue) && ($activeThreads<$this->_threads))
        {
            $request = array_shift($this->_queue);
            curl_multi_add_handle($mh, $h=self::createHandle($request[0], $request[2], $request[3], $request[4]));
            $hData[(string)$h] = array($request[1], $request[5]);
            $activeThreads++;
        }

        $active = null;
        do {
            do $mrc = curl_multi_exec($mh, $active);
            while ($mrc == CURLM_CALL_MULTI_PERFORM);
            while($info=curl_multi_info_read($mh)){
                if(!empty($this->_queue)){
                    $request = array_shift($this->_queue);
                    curl_multi_add_handle($mh, $h=self::createHandle($request[0], $request[2], $request[3], $request[4]));
                    $hData[(string)$h] = array($request[1], $request[5]);
                }
                call_user_func(
                    $hData[(string)$info['handle']][0],
                    curl_getinfo($info['handle']), 					// $info
                    curl_multi_getcontent($info['handle']), 		// $content
                    $hData[(string)$info['handle']][1],				// $data
                    microtime(true) - $startTime					// $execTime
                );
                unset($hData[(string)$info['handle']]);
                curl_multi_remove_handle($mh, $info['handle']);
                curl_close($info['handle']);
            }
            if (curl_multi_select($mh) == -1) usleep(100);
        } while ($active && $mrc == CURLM_OK);

        curl_multi_close($mh);
    }

    /**
     * Создание cURL дескриптора.
     * @param string $url
     * @param string $proxy
     * @param string $browser
     * @param array $curlOpt
     * @return resource cURL дескриптор.
     */
    protected static function createHandle($url, $proxy=null, $browser=null, $curlOpt=null)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        if($proxy !== null) curl_setopt($ch, CURLOPT_PROXY, $proxy);
        if($browser !== null) curl_setopt($ch, CURLOPT_USERAGENT, $browser);
        if(is_array($curlOpt) && !empty($curlOpt)) curl_setopt_array($ch, $curlOpt);
        return $ch;
    }

    /**
     * @param int $threads Кол-во потоков.
     * @throws InvalidArgumentException
     */
    public function setThreads($threads)
    {
        if(!is_int($threads)) throw new InvalidArgumentException('Кол-во потоков должно быть целым числом.');
        if($threads < 2) throw new InvalidArgumentException('Кол-во потоков должно быть > 1.');
        $this->_threads = $threads;
    }

    /**
     * @return int Кол-во потоков.
     */
    public function getThreads()
    {
        return $this->_threads;
    }

    /**
     * Быстрый cURL запрос.
     * @param string $url URL запроса.
     * @param string $proxy HTTP-прокси, через который будет направляться запрос.
     * @param string $browser Браузер (эквивалент CURLOPT_USERAGENT для сURL).
     * @param array $curlOpt Дополнительные опции для cURL, если необходимо (http://php.net/manual/ru/function.curl-setopt.php).
     * @return array Результат выполнения запроса.
     */
    static function get($url, $proxy=null, $browser=null, $curlOpt=null)
    {
        $ch = self::createHandle($url, $proxy, $browser, $curlOpt);
        $r = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        return array_merge($info, array('result'=>$r));
    }
}