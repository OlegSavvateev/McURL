McURL
=====
PHP класс для многопоточной работы с cURL.

Пример использования
--------------------
    $mc = new McURL();
    for($i=1; $i<=100; $i++){
        $mc->add(/*URL*/, 'callback');
    }
    $mc->run();

    function callback($info, $content, $data, $execTime){
        . . .
    }