<?php
/**
 * Пример работы с McURL.
 * Получение вопросов с первых 20 страниц сайта toster.ru, в 10 потоков (по умолчанию).
 * 
 * @author Oleg Savvateev <o.savvateev@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

require_once('../McURL.php');
require_once('phpQuery-onefile.php');

$mc = new McURL();
for($i=1; $i<=20; $i++){
    $mc->add('http://toster.ru/questions/latest?page='.$i, 'callback');
}
$mc->run();

function callback($info, $content, $data, $execTime){
    phpQuery::newDocumentHTML($content);
    foreach(pq('div.questions_list_item') as $div){
        $title = pq($div)->find('div.info')->find('div.title')->find('a')->html();
        echo $title . '<br />';
    }
}