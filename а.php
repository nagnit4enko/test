<?php

namespace App\Helpers;

use \App\Helpers\UserHelper;
use \App\Models\CatalogModel;
use \App\Models\ClientModel;

class OrderHelper extends \App\Models\OrderModel {
  
  const ORDER_DELETED_MASK    = 1;    // 0 - Удалён
  const ORDER_CANCELED_MASK   = 2;    // 1 - Отменён
  const ORDER_ACCEPTED_MASK   = 4;    // 2 - Потверждён
  const ORDER_PROCESSED_MASK  = 8;    // 3 - принят в работу
  const ORDER_ONDELIVERY_MASK = 16;   // 4 - У доставщика
  const ORDER_TOOK_MASK       = 32;   // 5 - Товар доставлен
  const ORDER_PICKUP_MASK     = 1024; // 10 - самовывоз
  const ORDER_DELIVERY_MASK   = 2048; // 11 - доставка
  const ORDER_REFUND_MASK     = 4096; // 12 - возврат
  const ORDER_PAYED_MASK      = 8192; // 13 - оплачен

  function __construct($config){
    $this->config = $config;
  }
 
  public function addressFormat($address){
    $address = preg_replace_callback( '/^(\d{0,2}[а-яА-ЯеЕЁё\s,-]+)\s*(\d{1,3}\s*[а-г]*)?\s*(\/\s*\d{0,2}[а-я]*)?\s*(к\s*\d{0,2}|корпус\s*\d{0,2})?\s*(-|оф|офис|кв)?\s*(\d{0,3}+)?/iu', function($match){
      $result = [];
      $result[] = trim($match[1]); // Название улицы
      $result[] = str_replace(' ', '', $match[2]); // Номер дома
      if (!empty($match[3])) {
        $result[] = str_replace(' ', '', $match[3]); // Дробь
      }
      if (!empty($match[4])) {
        $result[] = $match[4]; // Корпус
      }
      if (!empty($match[6])) {
        $result[] = !empty($match[5]) && $match[5] != '-' ? 'оф' : 'кв'; // Офис|Квартира
        $result[] = $match[6]; // Номер квартиры
      }
      
      return implode(' ', $result);
    }, $address);

    return $address;
  }

  public function phoneFormat($phone){
  
    if (preg_match( '/^(\+\d)?(\d{3})(\d{3})(\d{2})(\d{2})$/', $phone,  $match )) {
      $phone = !empty($match[1]) ? $match[1] : '+7';
      $phone .= ' ('.$match[2].')';
      $phone .= ' '.$match[3].'';
      $phone .= ' '.$match[4].'-'.$match[5].'';
    }
    return $phone;
  }
  
  public function getCurrentOrderSum($items, $count, $delivery = false){
    $delivery_sum = $total_sum = 0;
    foreach ($items as $res) {
      if (isset($res['properties']['delivery']) && $res['properties']['delivery']['value'] > 0) {
        $total_sum += $delivery_sum += $res['price'] * $count[$res['id']];
      } else {
        $total_sum += $res['price'] * $count[$res['id']];
      }
    }
    if ($delivery) {
      return [$total_sum, $delivery_sum];
    }
    return $total_sum;
  }

  public function getOrderCount($where = []){
    $result = $this->table('orders')->count('order_id')->where($where)->get();
    if (empty($result)) {
      return 0;
    }
    return $result[0]['cnt'];
  }
  
  public function checkFlags($mask){
    $result = [];
    foreach ($this->config['flags_cls'] as $i => $cls) {
      if ($mask & (1 << $i)) {
        $result[] = [$cls, $this->config['flags_name'][$i]];
      }
    }
    
    return $result;
  }

  public function getMethodPay($method){
    return $this->config['pay_type'][$method];
  }

  function getCoordForAgents($address){
  
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, 'https://geocode-maps.yandex.ru/1.x/?apikey=6b7cdd92-fc43-4f96-9452-63bdc7525da9&format=json&geocode='.urlencode($address));
    $data_coords_json = curl_exec($ch);

    $data_coords = json_decode($data_coords_json, true);

    if(!isset($data_coords['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos'])) {
      return false;
    }

    return trim($data_coords['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos']);
  }

  public function getOrderList($opts){
    $default = [
      'where' => [],
      'sort' => $this->config['default_sort'],
      'offset' => 0,
      'limit' => 10000
    ];
    $opts = array_merge($default, $opts);
    list($result, $user_ids, $item_ids, $client_ids, $phone_ids, $address_ids) = $this->getList($opts['where'], $opts['sort'], $opts['offset'], $opts['limit']);
    
    if ($client_ids) {
      $client = new ClientModel();
      $clients = $client->getList(['client_id' => $client_ids]);
      $phones = $client->getPhoneList(['client_phone_id' => $phone_ids]);
      $address = $client->getAddressList(['address_id' => $address_ids]);
    }
    
    if ($item_ids) {
      $items = new CatalogHelper($this->config);
      $result_items = $items->getCatalogList([
        'where' => ['id' => array_unique($item_ids)], 
        'sort' => [], 
        'offset' => 0, 
        'limit' => 100, 
        'need_price' => true, 
        'need_cat_name' => false, 
        'need_image' => false, 
        'need_trade_offers' => false, 
        'need_users' => false
      ]);
    }
    
    if ($user_ids) {
      $users = new UserHelper();
      $result_users = $users->getUserInfo($user_ids);
    }
    
    foreach ($result as &$row) {
      
      $row['client'] = $clients[$row['client_id']];
      $row['address'] = $address[$row['address_id']];
      $row['operator_id'] = $result_users[$row['operator_id']];

      if (isset($result_users[$row['deliveryman']])) {
        $row['deliveryman'] = $result_users[$row['deliveryman']];
      }

      $row['phones'] = [];
      foreach ($row['phone_id'] as $i => $phone_id) {
        $row['phone_id'][$i] = $this->phoneFormat($phones[$phone_id]['phone']);
        $row['phones'][] = array('client_phone_id' => $phone_id, 'phone' => $phones[$phone_id]['phone']);
      }
      
      $last_id = 0;
      foreach ($row['item_ids'] as $f => $item_id) {
        if ($last_id != $item_id) {
          $row['item_ids'][$f] = $result_items[$item_id];
          $last_id = $item_id;
        } else {
          unset($row['item_ids'][$f]);
        }
        
        if (!isset($row['item_ids_cnt'][$item_id])) {
          $row['item_ids_cnt'][$item_id] = 0;
        }
        $row['item_ids_cnt'][$item_id]++;
      }
      $row['item_ids'] = array_values($row['item_ids']);
    }
    
    return $result;
  }
  
  public function checkOrderUpdate($order_id){
    $date = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

    // Проверяем к какому дню относится товар
    $result = array_values($this->table('orders')->select('order_id,date_create')->where([
      'order_id' => $order_id
    ])->get())[0];

    if (!$result) {
      return false;
    }

    if ($result['date_create'] < $date) {
      return 'Нельзя удалить заказы из предыдущего дня';
    }
    return true;
  }

  public function cahcelOrder($order_id){

    if (($res = $this->checkOrderUpdate($order_id)) !== true) {
      return $res;
    }

    $this->table('orders')->where(['order_id' => $order_id])->update(['flags+' => self::ORDER_CANCELED_MASK]);
    return true;
  }

  public function getPhoneList($where = [], $limit = 0){
    $result = $this->phoneList($where, $limit);
    return $result;
  }
  
  public function getClientList($where = []){
    $clients = $this->clientList($where);
    return $clients;
  }
  
  public function getAddressList($where = []){
    $address = $this->addressList($where);
    return $address;
  }
  
  public function setDeliveryMan($order_id, $delivery_id){
    if (($res = $this->checkOrderUpdate($order_id)) !== true) {
      return $res;
    }
    $this->table('orders')->where(['order_id' => $order_id])->update(['deliveryman' => $delivery_id]);
    return true;
  }

  public function searchForPhone($query, $limit = 0){
    list($result, $client_ids) = $this->phoneSearch($query, $limit = 0);
    
    $address = $clients = [];
    if (!empty($client_ids)) {
      $clients = $this->getClientList(['client_id' => $client_ids]);
      $address = $this->getAddressList(['client_id' => $client_ids]);
    }

    return ['phone' => array_values($result), 'client' => $clients, 'address' => $address];
  }
  
  public function saveOrder(){
    
  }
}
