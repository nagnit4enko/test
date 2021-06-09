<?php

namespace App\Controllers;

use \App\Core\Auth;
use \App\Core\Prices;
use \App\Helpers\OperatorHelper;
use \App\Helpers\OrderHelper;
use \App\Templates\OrderTemplate;



class Orders extends \App\Core\Controller {

  function __construct(){
    parent::__construct();

    if (!$this->userHelper->checkFlags(ADMIN_MASK | OPERATOR_MASK | DEVELOPER_MASK)) {
      $this->accessDenied();
    }

    \App::loadConfig('Orders');
    $this->config = \App::getConfig();
    $this->helper = new OrderHelper($this->config);
    $this->Template = new OrderTemplate($this->template->ui);
    $this->prices = new Prices();
    $this->operator = new OperatorHelper();
  }

  public function index($input){
    
    $page = new \App\Core\Pagination($this->view);
    $offset = $page->getOffset(isset($input['page_id']) ? $input['page_id'] : 1, $this->config['admin_items']);
    
    
    $where = [];

    $result = $this->helper->getOrderList([
      'where' => $where,
      'sort' => ['order_id' => 'desc'],
      'offset' => $offset,
      'limit' => $this->config['admin_items']
    ]);
    
          // Получаем доставщиков
      $delivery_res = $this->operator->getDeliveryMens();

     // $dropDown = $this->template->ui->dropDownButton('Доставщик', $delivery_res);
      //$delivery_res[0] = ['title' => 'Выберите'];
      $dropDown = $this->Template->select([
        'id' => '%d',
        'list' => $delivery_res
      ]);

    // Собираем пагинацию
    $pagination = $page->getPagination($this->helper->getOrderCount($where));
    $pagination = $this->template->sAdminPagination($pagination);
    
    $content = $this->Template->renderOrders('', $result, $pagination, $dropDown);
    
    $this->template->wrapPage(['Созданные заказы', 'Управление заказами'], '', $content, '');
  }

  function ordersPerDay($input){

    $date = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
    
    $user_id = Auth::id();

    if (!isset($input['day_id']) && !($day_id = $this->operator->checkStarted($user_id, $date))) {
      $result = $this->Template->startDayOperator();
      $content = $this->template->ui->panel($result);
    } else {
      
      \App::addStatic('AdminOrders.js');

      $operator_info = $this->operator->getOperatorDayInfo(isset($input['day_id']) ? $input['day_id'] : $day_id);

      $where = [];
      if (isset($input['day_id'])) {
        $where['date_create>='] = $date;
        $where['date_create<='] = mktime(23, 59, 59, date('m'), date('d'), date('Y'));;
        $date = $operator_info['date_start'];
      } else {
        $where['date_create>'] = $date;
      }

      // Если смотрит админ тогда не учитываем вывод по оператору
      if ($this->userHelper->checkFlags(OPERATOR_MASK)) {
        //$where['orders.operator_id'] = $user_id;
      }

      // получаем все заказы за определённый день
      $result = $this->helper->getOrderList([
        'where' => $where,
        'sort' => ['date_create' => 'asc'],
      ]);

      // Получаем доставщиков
      $delivery_res = $this->operator->getDeliveryMens();

     // $dropDown = $this->template->ui->dropDownButton('Доставщик', $delivery_res);
      //$delivery_res[0] = ['title' => 'Выберите'];
      $dropDown = $this->Template->select([
        'id' => '%d',
        'list' => $delivery_res
      ]);
      $title = 'Отчёт за '.date('d.m.Y', $date).' - '.$this->userHelper->getUserName($this->user);
      $print_button = '';
      if (isset($input['day_id'])) {
        $button = '<button data-id="'.$input['day_id'].'" class="print btn btn-primary btn-addon"><i class="fa fa-print pull-right"></i> Распечатать отчёт</button>';
        $print_button = '<button data-id="'.$input['day_id'].'" class="print btn btn-primary btn-addon pull-right"><i class="fa fa-print pull-right"></i> Распечатать отчёт</button>';
      } else {
        $button = '<button id="save" class="btn btn-info "> Закрыть рабочий день </button>';
        $print_button = '<button onclick="location.href=\'/admin/orders/create/\'" class="btn btn-info btn-addon pull-right "><i class="fa fa-plus pull-right"></i> Добавить новый заказ </button>';
      }
      $content = $this->Template->renderOrders($title, $result, $button, $dropDown, $print_button, $day_id, $this->genHash('day'.$day_id));
    }

    $this->template->wrapPage(['Обработанные заказы','Здесь отображаются все заказы принятые за день'],'', $content,'AdminOrders.initOrdersDay()');
  }

  public function getStarted($input){

    $date = time();
    $user_id = Auth::id();

    // Проверяем есть ли такой 
    if (!$this->operator->checkStarted($user_id, $date)) {
      $this->operator->setStarted($user_id);
    } 

    $this->redirect('/admin/orders/day/');
  }

  public function printReport($input){

    if (!isset($input['id']) || empty($input['id'])) {
      exit('Access denied');
    }

    $operator_info = $this->operator->getOperatorDayInfo( $input['id']);

    $where = [];

    list($d,$m,$y) = explode('.',date('d.m.Y', $operator_info['date_start']));

    ///$where['orders.date_create>='] = $operator_info['date_start'];
    //$where['orders.date_create<='] = $operator_info['date_end'];
    $where['orders.date_create>='] = mktime(0, 0, 0, $m, $d, $y);;
    $where['orders.date_create<='] = mktime(23, 59, 59, $m, $d, $y);;
    $where['orders.flags&'] = \App\Helpers\OrderHelper::ORDER_ONDELIVERY_MASK | \App\Helpers\OrderHelper::ORDER_PAYED_MASK | \App\Helpers\OrderHelper::ORDER_PICKUP_MASK;

    // получаем все заказы за определённый день
    $result = $this->helper->getOrderList([
     'where' => $where,
     'sort' => ['date_create' => 'asc'],
    ]);

    //$result = $this->operator->getDeliveryMan($id);
    $order_sum_on_day = 0;
    $delivery_info = $operators_info = $item_info = $order_sum_info = [];
    $payment_types = [];
    $order_new_info = [];
    foreach ($result as $key => $value) {

      // собираем типы оплат
      $method = $this->helper->getMethodPay($value['payment_type']);
      if (!isset($payment_types[$method])) {
        $payment_types[$method] = 0;
      }
      $payment_types[$method] += $value['order_sum'];

      $delivery_sum = 0;

      // собираем инфу о товаре
      foreach ($value['item_ids'] as $key2 => $value2) {

        $cnt = $value['item_ids_cnt'][$value2['id']];
        if (!isset($item_info[$value2['id']])) {
          $value2['count'] = 0;
          $value2['order'] = 0;
          $item_info[$value2['id']] = $value2;
        } 

        $item_info[$value2['id']]['count'] += $cnt;
        $item_info[$value2['id']]['order'] += $cnt * $value2['price'];

        if (!isset($order_new_info[$value['order_id']])) {
          $order_new_info[$value['order_id']] = ['title' => [], 'price' => 0];
        }

        $order_new_info[$value['order_id']]['title'][] = $value2['title'];
        $order_new_info[$value['order_id']]['price'] += $cnt * $value2['price'];

        // если есть доставки товара
        if (isset($value2['properties']['delivery']) && !empty($value2['properties']['delivery'])) {
          $delivery_sum = ceil($value2['price']);
        }
      }

      // Добавляем оператора
      $operators_info[$value['operator_id']['user_id']] = $value['operator_id']['name'];

      // считаем всю сумму оплаченных товаров
      $order_sum_on_day += $value['order_sum'];

      // Собираем инфу о доставщиках
      $check_flags = $value['flags'] & OrderHelper::ORDER_PICKUP_MASK;

      $deliveryman_id = $check_flags ? -1 : $value['deliveryman']['user_id'];
      if ($deliveryman_id > 0) {
        if (!isset($delivery_info[$deliveryman_id])) {
          $delivery_info[$deliveryman_id] = [
            'name'              => $value['deliveryman']['name'],
            'delivery_cnt'      => 0, // количество доставок
            'delivery_sum'      => 0, // сумма заказа
            'delivery_paid'     => 0, // есть ли платные доставки
            'delivery_discount' => 0, // скидка в заказе
            'payment_type'      => []
          ];
        }

      } else if ($check_flags && !isset($delivery_info[$deliveryman_id])) {
        $delivery_info[$deliveryman_id] = [
            'name'  => 'Самовывоз',
            'delivery_cnt'      => 0, // количество доставок
            'delivery_sum'      => 0, // сумма заказа
            'delivery_paid'     => 0, // есть ли платные доставки
            'delivery_discount' => 0, // скидка в заказе
            'payment_type'      => []
          ];
      }


      $delivery_info[$deliveryman_id]['delivery_cnt']++;
      $delivery_info[$deliveryman_id]['delivery_sum'] += $value['order_sum'];

      $delivery_info[$deliveryman_id]['delivery_paid'] += $delivery_sum;
        
      // количество платных доставок
      if ($delivery_sum) {
         $delivery_info[$deliveryman_id]['delivery_paid_cnt'] ++;
      }

      $delivery_info[$deliveryman_id]['delivery_discount'] += $value['discount'];

      if (!isset($delivery_info[$deliveryman_id]['payment_type'][$method])) {
        $delivery_info[$deliveryman_id]['payment_type'][$method] = 0;
      } 
          
      $delivery_info[$deliveryman_id]['payment_type'][$method] += $value['order_sum'];
    }


    $content = $this->Template->printPage($delivery_info, $item_info, $payment_types, $order_sum_on_day, $operators_info, $order_new_info, count($order_new_info));
    \App::addStatic('print.css');
    \App::addStatic('admin/css/print-order.css');
    $this->template->wrap->page('', '', $content, '');
  }

  public function endWorkDay($input){

    if (!$this->checkHash('day'.$input['day_id'])) {
      $this->template->wrap->renderJSON([ERROR, 'Access denied']);
    }

    $date_start = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
    $date_end   = mktime(23, 59, 59, date('m'), date('d'), date('Y'));

    $count = $this->helper->getOrderCount([
      'date_create>=' => $date_start,
      'delivery_date<=' => $date_end
    ]);

    $this->operator->closeWorkingDay($input['day_id'], $count);

    $this->template->wrap->renderJSON([SUCCESS, $input['day_id']]);
  }

  public function show($input){
    
    $result = array_values($this->helper->getOrderList([
      'where' => ['order_id' => $input['order_id']],
      'limit' => 1
    ]))[0];
    
    if (empty($result)) {
      $this->redirect('/admin/orders/');
    }

    \App::addStatic('AdminOrders.js');

    // Статусы заказа
    $status_mask = $this->helper->checkFlags($result['flags']);
   
    $result_status = '<span class="status-delivery label bg-'.$status_mask[0][0].' m-l-xs">'.$status_mask[0][1].'</span>';

    $orders = $this->Template->orderBlockRenderRow($result);
    list($total_sum, $delivery) = $this->helper->getCurrentOrderSum($result['item_ids'], $result['item_ids_cnt'], true);

    $options = '';
    foreach ($this->config['pay_type'] as $key => $value) {
      $options .= '<option '.($result['payment_type'] == $key || $this->config['pay_default'] == $key ? 'selected' : '').' value="'.$key.'">'.$value.'</option>';
    }

    $order_block = $this->Template->getOrderDetailBlock('', $orders, $total_sum, $result['client_sum'], $result['short'] ? 'Сдача: '.$result['short'] : 'Без сдачи', $result['descr'], $options);
    
    $method_pay = $this->helper->getMethodPay($result['payment_type']);

    // Получаем доставщиков
    $dropDown = $this->Template->select([
      'id' => '%d',
      'list' => $this->operator->getDeliveryMens()
    ], $row['deliveryman']['user_id']);

    //if ($this->userHelper->checkFlags(ADMIN_MASK | MODER_MASK)) {
      $cancel_button = '<button class="cancel-order btn btn-danger btn-sm" data-toggle="modal" data-target="#confirm">Отменить заказ</button>';
      $cancel_block = $this->Template->addRowBlock('Отмена заказа', $cancel_button);
      $cancel_block .= $this->template->ui->setModal('confirm', 'Подтверждение действия', 'Вы действительно хотите отменить заказ?', [
        ['class' => 'btn btn-danger accept', 'data-order-id' => $input['order_id'], 'title' => 'Отменить заказ'],
        ['class' => 'btn btn-default cancel', 'data-dismiss' => 'modal',  'title' => 'Закрыть окно'],
      ]);
    //}

    // Количество закаов от клиента
    $number_orders = $this->helper->getOrderCount([
      'client_id>=' => $result['client_id']
    ]);

    // Количество купленных товаров
    $number_count_orders = $this->helper->getOrderCount([
      'client_id>=' => $result['client_id'],
      'flags&' => \App\Helpers\OrderHelper::ORDER_TOOK_MASK
    ]);

    // Количество отменённых заказов
    $number_cancelled_orders = $this->helper->getOrderCount([
      'client_id>=' => $result['client_id'],
      'flags&' => \App\Helpers\OrderHelper::ORDER_CANCELED_MASK
    ]);


    $content = $this->Template->orderViewBlock($result, $result_status, $order_block, $total_sum, $method_pay, $delivery, $dropDown, $cancel_block, $number_orders, $number_count_orders, $number_cancelled_orders);

    $this->template->wrapPage(['Заказ ID ('.$input['order_id'].') | № '.$input['order_id'].', создан '.date('d.m.Y H:i:s', $result['date_create']), 'Просмотр параметров заказа'], '', $content, 'AdminOrders.initOrder();');
  }
  
  public function cancelOrder($input){
    
    if (!$this->userHelper->checkFlags(ADMIN_MASK | MODER_MASK )) {
     // $this->accessDenied();
    }

    // Отменяем заказ
    if (($res = $this->helper->cahcelOrder($input['order_id'])) !== true){
      $this->template->wrap->renderJSON([ERROR, $res]);
    }

    $this->template->wrap->renderJSON([SUCCESS, 'Заказ был успешно отменён']);
  }

  public function createOrder($input){
    
    $result = [];
    if (isset($input['id'])) {
      $result = array_values($this->helper->getOrderList([
        'where' => ['order_id' => $input['id']],
        'limit' => 1
      ]))[0];
    }
    
    \App::addStatic(
      "https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/css/bootstrap-datetimepicker.css",
      'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js',
      "https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment-with-locales.min.js",
      'https://selectize.github.io/selectize.js/css/normalize.css',
      'AdminOrders.js',
     
      'selectize.bootstrap3.css',
      'selectize.js',
      'jquery.mask.js'
    );

    $options = '';
    foreach ($this->config['pay_type'] as $key => $value) {
      $options .= '<option '.($this->config['pay_default'] == $key ? 'selected' : '').' value="'.$key.'">'.$value.'</option>';
    }

    $order_detail = $this->Template->getOrderDetailBlock($this->Template->buttonSaveOrder(), '','','','','', $options);
    $panel = $this->Template->createOrderPage($order_detail);
    
    $this->template->wrapPage(['Добавление нового заказа', 'Обработка и распечатка поступающих заказов'], '', $panel, 'AdminOrders.initCreate('.json_encode($result, JSON_UNESCAPED_UNICODE).')');
  }
  
  public function onload($input){
    
    \App::loadConfig('Catalog');
    $this->config = \App::getConfig();
    $items = new \App\Helpers\CatalogHelper($this->config);

    if (isset($input['sections'])) {
      $result = array_values($items->getSectionList());
    } else {
      $new_result = $items->getCatalogList([
        'where' => ['catalog.flags&!' => 1],
        'sort' => ['last_edit' => 'desc'], 
        'offset' => 0, 
        'limit' => 10000,
      ]);
      $result = [];
      foreach ($new_result as $key => $value) {
        $result[$value['catalog_id']][$value['id']] = $value;
      }
    }
    
    $this->template->wrap->renderJSON($result);
  }
  
  public function search($input) {
    
    $result = $this->helper->searchForPhone(str_replace(['(',')',' ','-'], '', $input['q']), 1000);
    
    $this->template->wrap->renderJSON($result);
    //echo "{$input['callback']}(".json_encode($result).")";
  }
  
  public function streets($input) {
    
    $result = $this->helper->getStreets();
    //print_r($result);
    $this->template->wrap->renderJSON($result);
    //echo "{$input['callback']}(".json_encode($result).")";
  }
  
  public function save($input) {
    
    if(isset($_SESSION['ip']) && $_SESSION['last_post'] + 15 > time()) die('Флуд контроль');

    $_SESSION['last_post'] = time();
    $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];

    if (!isset($input['items']) || empty($input['items'])) {
      $this->template->wrap->renderJSON([ERROR, 'Товары не были переданы']);
    }
    
    if (!isset($input['client_id']) || empty($input['client_id'])) {
      if (!isset($input['client']) || empty($input['client'])) {
        $this->template->wrap->renderJSON([ERROR, 'Не переданы данные клиента']);
      }
    }
    
    if (!isset($input['address_id']) || empty($input['address_id'])) {
      if (!isset($input['address']) || empty($input['address'])) {
        $this->template->wrap->renderJSON([ERROR, 'Не указана улица клиента']);
      }
    }
    
    /*if (!isset($input['client_sum']) || empty($input['client_sum'])) {
      $this->template->wrap->renderJSON([ERROR, 'Не указана сумма от клиента']);
    }
    */
    if (!isset($input['date']) || empty($input['date'])) {
      $this->template->wrap->renderJSON([ERROR, 'Не указано время доставки заказа']);
    }
    
    if (!isset($input['time']) || empty($input['time'])) {
      $this->template->wrap->renderJSON([ERROR, 'Не указано время доставки заказа']);
    }
    
    if (!isset($input['order_pay_type']) || $input['order_pay_type'] > 4 || $input['order_pay_type'] < 0) {
      $this->template->wrap->renderJSON([ERROR, 'Неуказан тип платежа']);
    }
   
    if (($delivery_date = strtotime($input['date'].' '.$input['time'])) === false || ($delivery_date + 600) < time()) {
      $this->template->wrap->renderJSON([ERROR, 'Неправильная дата']);
    }
   
    $client_id = $input['client_id'];
    
    // Флаги для параметров заказа
    $flags = \App\Helpers\OrderHelper::ORDER_PROCESSED_MASK; // Принят в работу
    if (($address_id = $input['address_id']) == -1) {
      $flags += \App\Helpers\OrderHelper::ORDER_PICKUP_MASK; // Самовывоз
    } else {
      $flags += \App\Helpers\OrderHelper::ORDER_DELIVERY_MASK; // Доставка
    }
    
    // Подключаем обработчик работы с клиентами
    $client = new \App\Models\ClientModel();

    if (!$client_id) {
      
      // Создаём клиента
      $client_id = $client->create($input['client']);
    }

    $phones = [];

    // Добавляем телефон
    foreach ($input['phone'] as $phone) {
      if (preg_match('/\+?\d{10,12}/i', $phone)) {
        $phones[] = $client->addPhone($client_id, $phone);
      } else {
        if (empty($phone) || mb_strlen($phone) > 6) {
          $this->template->wrap->renderJSON([ERROR, 'Неправильно указан номер телефона']);
        }
        $phones[] = $phone;
      }
    }

    // Объеденяем телефоны
    $phones = implode(',', $phones);

    // Добавляем адрес
    if (!is_numeric($input['address_id'])) {
      $address_id = $client->addAddress($client_id, $this->helper->addressFormat($input['address']));
    }

    // Считаем итоговую сумму заказа
    $counts = [];
    $items = new \App\Helpers\CatalogHelper($this->config);
    $result = $items->getCatalogList([
      'where' => ['catalog.id' => array_unique($input['items'])],
      'limit' => 10000
    ]);

    foreach ($input['items'] as $key => $value) {
      if (!isset($counts[$value])) {
        $counts[$value] = 0;
      }
      $counts[$value]++;
    }
    
    $order_sum = $this->helper->getCurrentOrderSum($result, $counts);

    if (isset($input['order_id'])) {
      $this->helper->table('orders')->where(['order_id' => $input['order_id']])->update([
        'item_ids' => implode(',', $input['items']), 
        'date_create' => time(),
        'delivery_date' => $delivery_date,
        'client_sum' => $input['client_sum'],
        'client_id' => $client_id,
        'phone_id' => $phones,
        'address_id' => $address_id,
        'flags' => $flags,
        'payment_type' => $input['order_pay_type'],
        'descr' => $input['note_to_order'],
        'short' => $input['short'],
        'operator_id' => (int)Auth::id(),
        'order_sum' => $order_sum,
        'host_id' => \App::_getDomainId()
      ]);
      $this->template->wrap->renderJSON([SUCCESS, 'id' => $input['order_id']]);
    }
    
    $id = $this->helper->table('orders')->insertGetId([
      'item_ids' => implode(',', $input['items']), 
      'date_create' => time(),
      'delivery_date' => $delivery_date,
      'client_sum' => $input['client_sum'],
      'client_id' => $client_id,
      'phone_id' => $phones,
      'address_id' => $address_id,
      'flags' => $flags,
      'payment_type' => $input['order_pay_type'],
      'descr' => $input['note_to_order'],
      'short' => $input['short'],
      'operator_id' => (int)Auth::id(),
      'order_sum' => $order_sum,
      'host_id' => \App::_getDomainId()
    ]);
    
    $this->template->wrap->renderJSON([SUCCESS, 'id' => $id]);
  }
  
  public function updateDeliveryMan($input){

    if (!isset($input['delivery_id']) || empty($input['delivery_id'])) {
      $this->template->wrap->renderJSON([ERROR, 'Не выбран доставщик']);
    }

    if (!isset($input['order_id']) || empty($input['order_id'])) {
      $this->template->wrap->renderJSON([ERROR, 'Не выбран заказ']);
    }

    if (!($res = $this->operator->getDeliveryMan($input['delivery_id']))) {
      $this->template->wrap->renderJSON([ERROR, 'Переданный айди не является доставщиком']);
    }

    // Добавляем курьера в доставки и обновляем статус
    $this->helper->table('orders')->where(['order_id' => $input['order_id']])->update([
      'deliveryman' => $input['delivery_id'],
      'flags+' => \App\Helpers\OrderHelper::ORDER_ONDELIVERY_MASK,
    ]);

    $this->template->wrap->renderJSON([SUCCESS, 'Доставщик успешно установлен']);
  }

  public function printPage($input){

    if (!$input['order_id']) {
      exit;
    }
    
    \App::addStatic('print.css');
    
    $result = array_values($this->helper->getOrderList([
      'where' => ['order_id' => $input['order_id']],
      'limit' => 1
    ]))[0];
    
    $payment_method = $this->helper->getMethodPay($result['payment_type']);

    $date_start = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
    $date_end   = strtotime('tomorrow');

    // Получаем позицию счётчика
    $order_fake_id = $this->helper->getOrderCount([
      'delivery_date>=' => $date_start,
      'order_id<=' => $input['order_id']
    ]);

    $this->template->wrap->page('','', $this->Template->receipt($result, $payment_method, $order_fake_id), '');
  }
}
