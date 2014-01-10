McURL
=====
PHP класс для многопоточной работы с cURL.

Пример использования
--------------------
    $mc = new McURL();
    for($i=1; $i<=100; $i++){
        $mc->addRequest(/*URL*/, 'callback');
    }
    $mc->run();

    function callback($info, $content, $data, $execTime){
        . . .
    }