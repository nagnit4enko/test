<?php

namespace App\Templates;

class OrderTemplate {
  
  public function __construct($ui){
    $this->ui = $ui;
  }
  
  function blockOverlay($cls = 'calendar'){
    return <<<HTML
<span class="input-group-addon">
  <i class="glyphicon glyphicon-{$cls}"></i>
</span>
HTML;
  }
  
  public function startDayOperator(){
    return <<<HTML
<div class="text-center m-t-xxl m-b-xxl wrapper-xl start_work_day">
  <div class="h3 m-t-xxl">Чтобы продолжить, необходимо начать рабочий день</div>
  <div class="text-muted m-t-xl">Начнётся учёт вашего рабочего времени, а так же принятые и обработанные заказы</div>
  <button class="btn m-t-lg btn-info" onclick="location.href = '/admin/orders/getstarted/'">Начать рабочий день</button>
</div>
HTML;
  }

  public function inputGroup($opts){
    $default = array(
      'npadd' => 'input-group',
      'slabel' => '12 input-group',
      'sblock' => '12',
      'r' => 'left',
      'block' => ''
    );
    
    $default = array_merge($default, $opts);  
    return $this->ui->formGroup($default);
  }
  
  public function getOrderDetailBlock($button = '', $orders_block = '', $result_sum = 0, $sum = '', $short = 'Без сдачи', $descr = '', $opts = ''){
    return <<<HTML
<div class="panel panel-default">
    <div class="panel-heading">
      Детали заказа
    </div>
    <div class="table-responsive">
      <table class="table table-striped b-t b-light">
        <thead>
          <tr>
            <th class="text-center">№</th>
            <th>Блюдо</th>
            <th>Цена</th>
            <th>Количество</th>
            <th>Еденица измерения</th>
            <th>Скидка</th>
            <th>Сумма</th>
            <th style="width:30px;"></th>
          </tr>
        </thead>
        <tbody>
          {$orders_block}
        </tbody>
      </table>
      <div class="col-lg-12 m-t-sm m-b-sm">
        <div class="col-md-10 text-right">
          <b>Итого:</b>
        </div>
        <div id="result-sum" class="col-sm-2" style="padding-left: 30px;">{$result_sum}</div>
      </div>
    </div>
    <footer class="panel-footer">
      <div class="row m-t">
        <div class="col-sm-3 hidden-xs" style="padding-right;padding-right: 0;">
          <select id="order-pay-type" name="order_pay_type" class="input-sm form-control" tabindex="10">
            {$opts}
          </select>
        </div>
        <!--<div class="col-sm-3 text-center">
          <div class="form-group clearfix ">
            <div class="input-group">
              <input autocomplete="off" value="{$sum}" placeholder="Сумма у клиента" value="" class="form-control" name="client_sum" tabindex="11" type="text">
              <span class="input-group-addon">
                <i class=" fa fa-money"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="col-sm-2 text-center-xs">  
          <small id="client-sum" class="text-muted inline m-t m-b-sm" style="margin-top: 8px;">
            {$short}
          </small>
          <input name="short" type="hidden">
        </div>-->
        <div class="col-sm-4 text-right text-center-xs">                
          <div class="form-group">
            <textarea class="form-control" name="note_to_order" tabindex="12" placeholder="Примечание к заказу" rows="1">{$descr}</textarea>
          </div>
        </div>
        <div class="col-sm-5 text-center-xs">                
          <div id="delivery" class="form-group">
          </div>
        </div>
      </div>
      {$button}
    </footer>
</div>
HTML;
  }
  
  function buttonSaveOrder(){
    return <<<HTML
<div class="row text-center">
  <button id="save" class="btn btn-info m-r" tabindex="12">Оформить заказ</button>
  <button onclick="event.preventDefault(); location.href = '/admin/orders/day/';" class="btn btn-default">Отменить</button>
</div>
HTML;
  }
  
  public function createOrderPage($order_detail){
    $date = date('d.m.Y', time());
    return <<<HTML
<form method="POST" onsubmit="return false">
<div class="panel">
  <div class="row">
    <div class="col-lg-12">
      <div class="col-md-6">
        <div id="phone-numbers" class="m-t">
          <div class="form-group clearfix ">
            <label class="text-left col-sm-12 input-group control-label">Номер телефона <span id="count-orders"></span>:</label>
            <div class="input-group col-sm-12">
              <span class="input-group-addon">+7</span>
              <select id="phone" name="phone[]" class="" placeholder="Начните вводить номер телефона"></select>
            </div>
          </div>
        </div>
        <div id="client" class="hide">
          <div class="form-group clearfix ">
            <label class="text-left col-sm-12 input-group control-label">Имя клиента:</label>
            <div class="input-group col-sm-12">
              <input autocomplete="off" placeholder="Имя клиента, название компании" value="" class="form-control" name="client" tabindex="2" type="text">
              <input name="client_id" type="hidden">
            </div>
          </div>
        </div>
        <div class="block">
          <div class="form-group clearfix ">
            <label class="text-left col-sm-12 input-group control-label">Адрес доставки:</label>
            <div class="input-group col-sm-12">
              <select id="address" placeholder="Улица, номер дома, квартира" name="address" tabindex="3" ></select>
              <input name="address_id" type="hidden">
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6 m-t">
        <!--<div class="form-group clearfix ">
          <label class="text-left col-sm-12 input-group control-label">Дата и время доставки:</label>
          <div class="input-group col-sm-12">
            <div class="row">  
              <div class="col-md-9 ">
                <div class="date input-group" >
                  <input autocomplete="off" data-provide="datepicker" data-date-autoclose="true" placeholder="dd.mm.yyyy" value="{$date}" class="form-control" name="date" tabindex="4" type="text">
                  <span class="input-group-addon">
                    <i class="glyphicon glyphicon-calendar"></i>
                  </span>
                </div>
              </div>  
              <div class="col-md-3 ">
                <div class="input-group">
                  <input autocomplete="off" placeholder="14:52" class="form-control" name="time" tabindex="5" type="text">
                  <span class="input-group-addon">
                    <i class="glyphicon glyphicon-time"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>-->
        <div class="form-group clearfix ">
          <label class="text-left col-sm-12 input-group control-label">Категория:</label>
          <div class="input-group col-sm-12">
            <!--<div class="row">  
              <div class="col-md-6 ">-->
                <select id="sections" placeholder="Выберите категорию" name="sections"  tabindex="7" ></select>
              <!--</div>
              <div class="col-md-6 ">
                <select id="delivery" placeholder="Доставка" name="delivery"  tabindex="7" ></select>
              </div>
            </div>-->
          </div>
        </div>
        <div class="form-group clearfix ">
          <label class="text-left col-sm-12 input-group control-label">Блюдо:</label>
          <div class="input-group col-sm-12">
            <div class="row">  
              <div class="col-md-9 ">
                <select id="dish" placeholder="Укажите название блюда" name="dish"  tabindex="8" ></select>
                <input type="hidden" value="" name="dish_id">
              </div>
              <div class="col-md-3 ">
                <div class="input-group">
                  <input autocomplete="off" class="form-control" name="count" tabindex="9" type="number" style="width: 105px;border-top-right-radius: 4px;border-bottom-right-radius: 4px;">
                  <span class="input-group-addon" style="position: absolute;top: 0;z-index: 123;right: 0;padding: 8px 26px 8px 12px;border-left: 1px solid #cfdadd;">
                    <i class="icon-basket-loaded"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
{$order_detail}
<div class="modal fade" id="confirm" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="exampleModalLabel">Подтверждение</h3>
      </div>
      <div class="modal-body">
        Необходимо подтвердить создание заказа
      </div>
      <div class="modal-footer">
        <button type="button" tabindex="13" data-dismiss="modal" class="btn btn-primary" id="create">Сохранить и распечатать</button>
        <button type="button" tabindex="14" class="btn btn-secondary" id="close" data-dismiss="modal">Закрыть</button>
      </div>
    </div>
  </div>
</div>
</form>
HTML;
  }

  public function getDeliveryMan($row, $dropdown){
    //if (!empty($row['deliveryman'])) {
     // $dropdown = '<a href="/admin/users/'. $row['deliveryman']['user_id'] . '/map/">'.$row['deliveryman']['name'].'</a>';
    //} else {
      $id = $row['deliveryman']['user_id'];
      $dropdown = preg_replace_callback('/value="(\d+)"/i', function($m) use ($id){
        return 'value="'.$m[1].'" '.($m[1] == $id ? 'selected' : '');
      }, $dropdown);
      $dropdown = sprintf($dropdown, $row['order_id']);
    //}
    return $dropdown;
  }
  
  public function orderRow($order_fake_id, $row, $dropdown){
    $phones = implode(', ', $row['phone_id']);
    $date_create = date('d.m в H:i', $row['date_create']);
    $date = date('d.m в H:i', $row['delivery_date']);
    $total_sum = 0;
    $names = [];
    foreach ($row['item_ids'] as $res) {
      $names[] = $res['title'];
      $total_sum += $res['price'] * $row['item_ids_cnt'][$res['id']];
    }

    $class = '';
    $address = empty($row['address']) ? '' : $row['address']['address'];
    if (($row['flags'] & \App\Helpers\OrderHelper::ORDER_CANCELED_MASK)) {
      $class = 'cancelled';
      $dropdown = '-';
    } else if (($row['flags'] & \App\Helpers\OrderHelper::ORDER_PICKUP_MASK)) {
      $address = 'Самовывоз';
      $dropdown = '-';
    } else {
      $dropdown = $this->getDeliveryMan($row, $dropdown);
    }

    array_splice($names, 3);
    $order_name = implode(',', $names);
    return <<<HTML
<tr class="{$class}">
  <td><a href="/admin/orders/order/{$row['order_id']}/">{$order_fake_id}</a></td>
  <td><a href="/admin/orders/order/{$row['order_id']}/">{$order_name}</a></td>
  <td>{$total_sum}</td>
  <td>{$date_create}</td>
  <td><a href="/admin/orders/order/{$row['order_id']}/">{$address}</a></td>
  <td>{$dropdown}</td>
  <td><a href="/admin/orders/order/{$row['order_id']}/">{$phones}</a></td>
</tr>
HTML;
  }

  function select($opts, $sel = 0){
    $options = '<option value="0">Выберите курьера</option>';
    foreach ($opts['list'] as $k => $v) {
      $options .= '<option value="'.$k.'">'.$v['title'].'</option>';
    }
    return <<<HTML
<select class="deliveryman" data-id="{$opts['id']}" name="delivery_id">
  {$options}
</select>
HTML;
  }

  public function renderOrders($title, $result, $pagination, $dropdown, $button = '', $day_id = 0, $hash = ''){
    
    $orders = '';
    $result_order_id = 1;
    foreach ($result as $order_id => $row) {
      $orders .= $this->orderRow($result_order_id, $row, $dropdown);
      $result_order_id++;
    }
    
    return <<<HTML
  <div class="col-lg-12">
    <div class="row clearfix m-b-md">
      {$button}
    </div>
  </div>  
  <div class="panel col-lg-12">
    <h3 class="text-center m-b-md">{$title}</h3>
    <div class="table-responsive">
      <table class="table table-striped b-t b-light">
        <thead>
          <tr>
            <th>№</th>
            <th>Заказ</th>
            <th>Цена</th>
            <th>Дата заказа</th>
            <th>Адрес доставки</th>
            <th>Курьер</th>
            <th>Телефон</th>
          </tr>
        </thead>
        <tbody>
          {$orders}
        </tbody>
      </table>
    </div>
    <footer class="panel-footer">
      <div class="row">
        <div class="col-sm-4 hidden-xs">
          
        </div>
        <div class="col-sm-4 text-center text-center-xs">                
          {$pagination}
        </div>
        <div class="col-sm-4 text-right text-center-xs">    
      </div>
    </footer>
  </div>
  <div class="modal fade" id="confirm" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="exampleModalLabel">Подтверждение</h3>
      </div>
      <div class="modal-body">
        Вы собираетесь закрыть рабочий день
      </div>
      <div class="modal-footer">
        <button type="button" tabindex="12" data-id="{$day_id}" data-hash="{$hash}" data-dismiss="modal" class="btn btn-primary" id="create">Закрыть рабочий день</button>
        <button type="button" tabindex="13" class="btn btn-secondary" id="close" data-dismiss="modal">Закрыть окно</button>
      </div>
    </div>
  </div>
</div>
HTML;
  }
  
  public function orderBlockRenderRow($result){
    ob_start();
    foreach ($result['item_ids'] as $key => $value) {
      ?><tr>
        <td class="text-center"><?=($key+1)?></td>
        <td><a href="/admin/catalog/itemedit/<?=$value['id']?>/" target="_blank"><?=$value['title']?></a></td>
        <td><?=ceil($value['price'])?></td>
        <td><?=$result['item_ids_cnt'][$value['id']]?></td>
        <td><?=$value['abbreviated']?></td>
        <td>0</td>
        <td><?=($value['price'] * $result['item_ids_cnt'][$value['id']])?></td>
        <td></td>
      </tr><?
    }
    return ob_get_clean();
  }

  public function addRowBlock($title, $value){
    return <<<HTML
<div class="form-group m-b-xxs">
  <label class="col-sm-6 control-label text-left">{$title}</label>
  <div class="col-sm-6">
    <p class="form-control-static">{$value}</p>
  </div>
</div>
HTML;
  }

  public function orderViewBlock($result, $flags_status, $order_block, $total_sum, $method_pay, $delivery, $dropDown, $cancel_block, $number_orders, $number_count_orders, $number_cancelled_orders){

    $date_create = date('d.m.Y H:i:s', $result['date_create']);
    $price = new \App\Core\Prices();
    $date_delivery = date('d.m.Y H:i:s', $result['delivery_date']);
    $all_sum = $price->getPricesStrLang($total_sum, '%s Тенге');
    $delivery = $price->getPricesStrLang($delivery, '%s Тенге');
    $remaining_to_pay = $order_sum = $price->getPricesStrLang($result['order_sum'], '%s Тенге');
    $phones = implode(', ', $result['phone_id']);

    $paid_up = 0;
    // Если заказ оплачен
    if ($result['flags'] & \App\Helpers\OrderHelper::ORDER_PAYED_MASK) {
      $paid_up = $order_sum;
      $remaining_to_pay = 0;
    }

    $top_status = $result['flags'] > 1 ? '<span class="status">Принят в работу</span>' : '<span class="bg-danger">Заказ был удалён</span>';

    $address = $result['address']['address'];
    if (($result['flags'] & \App\Helpers\OrderHelper::ORDER_PICKUP_MASK)) {
      $address = $deliveryman = 'Самовывоз';
    } else {
      $deliveryman = $this->getDeliveryMan($result, $dropDown);
    }

    $redemption_prec = 0;
    if ($number_count_orders) {
      $redemption_prec = (($number_orders - $number_cancelled_orders) * 100) / $number_count_orders;
    }

    $button_print = '';
    if (!($result['flags'] & \App\Helpers\OrderHelper::ORDER_CANCELED_MASK)) {
      
      $button_print = '<button style="margin-top: 4px;" onclick="location.href = \'/admin/orders/create/'.$result['order_id'].'/\';" class="btn btn-warning btn-addon pull-right m-t-xs">
          <i class="fa fa-pencil pull-right"></i> 
          Изменить заказ
        </button><button style="margin-top: 4px;" onclick="AdminOrders.callPrint(\'/admin/orders/print/'.$result['order_id'].'/\');" class="btn btn-primary btn-addon pull-right m-r m-t-xs">
          <i class="fa fa-print pull-right"></i> 
          Распечатать заказ 
        </button>';
    }
    $result['client']['name'] = !empty($result['client']['name']) ? $result['client']['name'] : 'Не указано';
    return <<<HTML
<style>
      .zzz{
        font-family: Calibri;
        font-size: 16px;
        line-height: 22px;
        color: #333;
      }
      h1, h2, h3, h4, h5, h6{font-family: calibri !important;}
      .bold{font-weight: bold;}
      .clr_red{color: #b00;}
      .clr_green{color: #86ae2e;}
      .clr_orange{color: #e38430;}

      .status{
        background: #7dac44;
        color: #fff;
        font-weight: normal;
        font-size: 80%;
        padding: 5px 10px;
        border-radius: 5px;
        font-family: calibri;
        line-height: 13px;
        display: inline-block;
        margin-left: 10px;
      }

      .row_data_list{margin:0 -10px 30px;}
      .row_data{
        display: flex;
        justify-content: space-between;
        padding: 2px 10px;
        border-radius: 3px;
      }
      .result{
        background: rgba(0, 0, 0, 0.05);
        font-weight: bold;
        margin-right: 10px;
      }
      .row_data > div{}
      .row_data > div + div{}

</style>
<section class="zzz">
  <div class="">
    <h4 class="bold">
      
    </h4>
    <div class="row">
      <div class="col-lg-12 m-b-lg">
        {$button_print}
        <button style="margin-top: 4px;" onclick="location.href='/admin/orders/create/'" class="btn  btn-info btn-addon pull-right m-t-xs m-r"><i class="fa fa-plus pull-right"></i> Добавить новый заказ </button>

        <h4 class="bold pull-left">
          Заказ ID ({$result['order_id']}) | № {$result['order_id']}, создан {$date_create}
        </h4>
      </div>
    </div>
    <div class="row_data_list">
      <div class="row">
        <div class="col-md-6">
          <div class="row_data">
            <div>ФИО</div>
            <div>{$result['client']['name']}</div>
          </div>
          <div class="row_data">
            <div>Телефон</div>
            <div>{$phones}</div>
          </div>
          <div class="row_data">
            <div>Адрес доставки</div>
            <div>{$address}</div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="row_data">
            <div>Общая стоимость товаров</div>
            <div>{$all_sum}</div>
          </div>
          <div class="row_data clr_red">
            <div>Cтоимость с учётом скидок и наценок</div>
            <div>{$all_sum}</div>
          </div>
          <div class="row_data">
            <div>Доставка</div>
            <div>{$delivery}</div>
          </div>
          <div class="row_data result">
            <div>Итого</div>
            <div>{$order_sum}</div>
          </div>
        </div>
      </div>
    </div>
    <div class="row row-sm">
      <div class="col-md-4">
        <div class="panel panel-default">
          <div class="panel-heading">Параметры заказа</div>
          <div class="panel-body">
            <div class="form-horizontal">
              <div class="form-group m-b-xxs">
                <label class="col-sm-6 control-label text-left m-t-n-xxs">Создан</label>
                <div class="col-sm-6">
                  <p class="form-control-static">{$date_create}</p>
                </div>
              </div>
              <div class="form-group m-b-xxs">
                <label class="col-sm-6 control-label text-left m-t-n-xxs">Время доставки</label>
                <div class="col-sm-6">
                  <p class="form-control-static">{$date_delivery}</p>
                </div>
              </div>
              <div class="form-group m-b-xxs">
                <label class="col-sm-6 control-label text-left m-t-n-xxs">Способ оплаты</label>
                <div class="col-sm-6">
                  <p class="form-control-static">{$method_pay}</p>
                </div>
              </div>
              <div class="form-group m-b-xxs">
                <label class="col-sm-6 control-label text-left m-t-n-xxs">Статус заказа</label>
                <div class="col-sm-6">
                  <p class="form-control-static">{$flags_status}</p>
                </div>
              </div>
              <div class="form-group m-b-xxs">
                <label class="col-sm-6 control-label text-left m-t-n-xxs">Курьер</label>
                <div class="col-sm-6">
                  <p class="form-control-static">{$deliveryman}</p>
                </div>
              </div>
              {$cancel_block}
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="panel panel-default">
          <div class="panel-heading">
            <p class="m-b-none pull-right">          
              <span class="badge bg-primary m-r" data-toggle="tooltip" data-placement="top" title="Всего заказов">{$number_orders}</span>
              <span class="badge bg-success  m-r" data-toggle="tooltip" data-placement="top" title="Оплаченных заказов">{$number_count_orders}</span>
              <span class="badge bg-info" data-toggle="tooltip" data-placement="top" title="Отменённых заказов">{$number_cancelled_orders}</span>
            </p>
            Покупатель
          </div>
          <div class="panel-body">
            <div class="form-horizontal">
              <div class="form-group m-b-xxs">
                <label class="col-sm-6 control-label text-left m-t-n-xxs">ФИО</label>
                <div class="col-sm-6">
                  <p class="form-control-static">{$result['client']['name']}</p>
                </div>
              </div><div class="form-group m-b-xxs">
                <label class="col-sm-6 control-label text-left m-t-n-xxs">Телефон</label>
                <div class="col-sm-6">
                  <p class="form-control-static">{$phones}</p>
                </div>
              </div><div class="form-group m-b-xxs">
                <label class="col-sm-6 control-label text-left m-t-n-xxs">Адрес доставки</label>
                <div class="col-sm-6">
                  <p class="form-control-static">{$result['address']['address']}</p>
                </div>
              </div><!--<div class="form-group m-b-xxs">
                <label class="col-sm-6 control-label text-left m-t-n-xxs">Процент выкупа</label>
                <div class="col-sm-6">
                  <p class="form-control-static">{$redemption_prec}%</p>
                </div>
              </div>-->
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="panel panel-default">
          <div class="panel-heading">Информация по оплатам</div>
          <div class="panel-body">
            <div class="form-horizontal">
              <div class="form-group m-b-xxs">
                <label class="col-sm-6 control-label text-left m-t-n-xxs">К оплате</label>
                <div class="col-sm-6 text-right">
                  <p class="form-control-static">{$order_sum}</p>
                </div>
              </div><div class="form-group m-b-xxs">
                <label class="col-sm-6 clr_green control-label text-left m-t-n-xxs">Оплачено</label>
                <div class="col-sm-6 text-right">
                  <p class="form-control-static">{$paid_up}</p>
                </div>
              </div><div class="form-group m-b-xxs">
                <label class="col-sm-6 clr_orange control-label text-left m-t-n-xxs">Осталось оплатить</label>
                <div class="col-sm-6 text-right">
                  <p class="form-control-static">{$remaining_to_pay}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div> 
    </div>
    {$order_block}
  </div>
</section>
HTML;
  }

  public function receipt($result, $pay_method, $order_fake_id){
    $client = $operator = $worker = '';
    $total_sum = 0;
    foreach ($result['item_ids'] as $pos_id => $row) {
      $client .= '<tr>
					<td>'.($pos_id+=1).'</td>
					<td>'.$row['title'].'</td>
          <td>'.ceil($row['price']).'</td>
					<td>'.$result['item_ids_cnt'][$row['id']].'</td>
					<td>'.($row['price'] * $result['item_ids_cnt'][$row['id']]).'</td>
				</tr>';
      $operator .= '<tr>
					<td>'.$pos_id.'</td>
					<td>'.$row['title'].'</td>
          <td>'.ceil($row['price']).'</td>
					<td>'.$result['item_ids_cnt'][$row['id']].'</td>
					<td>'.($row['price'] * $result['item_ids_cnt'][$row['id']]).'</td>
				</tr>';
      $worker .= '<tr>
					<td>'.$pos_id.'</td>
					<td>'.$row['title'].'</td>
					<td>'.$result['item_ids_cnt'][$row['id']].'</td>
				</tr>';
      $total_sum += $row['price'] * $result['item_ids_cnt'][$row['id']];
    }
    
    $phones = implode(', ', $result['phone_id']);
    ob_start();
    include ROOT . '/App/Views/admin/print/tema.php';
    return ob_get_clean();
  }

  public function setRow(){
    $block = '<td>'.implode('</td><td>', func_get_args()).'</td>';
    return <<<HTML
<tr>{$block}</tr>
HTML;
  }

  public function setRowDelivery($num, $name, $count, $count2, $payments, $order){
    $block = '';
    foreach ($payments as $key => $price) {
      $block .= '<div class="df just same" style="">
      <div>'.$key.':</div>
      <div><div class="brd_btm">'.$price.'</div></div>
    </div>';
    }
    return $this->setRow($num, $name, $count, $count2, $block, $order);
  }


   public function printPage($delivery_info, $item_info, $payment_types, $order_sum_on_day, $operators_info, $order_new_info, $format_count){

    $pay_types = $delivery_block = $items_block = '';
    $num = 1;
    foreach ($order_new_info as $key => $value) {
      $items_block .= $this->setRow($num++, implode(', ', $value['title']), /*ceil($value['price']), $value['count'],*/ ceil($value['price']));
    }

    $num = 1;
    foreach ($delivery_info as $key => $value) {
      $delivery_block .= $this->setRowDelivery($num++, $value['name'], $value['delivery_cnt'], $value['delivery_paid_cnt'], $value['payment_type'], $value['delivery_sum']);
    }

    foreach ($payment_types as $key => $value) {
      $pay_types .= '<tr>
          <td>'.$key.'</td>
          <td><div class="brd_btm">'.$value.'</div></td>
        </tr>';
    }

    $title = 'Оператор';
    if (count($operators_info) > 1) {
      $title = 'Операторы';
    }
    $operators_info = implode(', ', $operators_info);
    $date = date('d.m.Y H:i');
    return  <<<HTML
<div class="df top w3">
  <div>
    <div class="center">
      <div class="bold">Sushi tema Усть-Каменогорск</div>
      Дата сдачи смены: {$date} <br />
      {$title}: {$operators_info}
    </div>
    <div class="bold center">Проданные позиции</div>
    <table class="full-table">
      <tr>
        <th class="col_n">#</th>
        <th class="col_name">Наименование</th>
        <!--<th class="col_price">Цена</th>
        <th class="col_count">Кол-во</th>-->
        <th class="col_result">Сумма</th>
      </tr>
      {$items_block}
    </table>
    <div class="">
      <div class="center m-b bold">Информация по курьерам</div>
      <table class="full-table">
        <tr>
          <th class="col_n">#</th>
          <th class="col_name">Курьер</th>
          <th class="col_price">Количество доставок</th>
          <th class="col_count">Платные доставки</th>
          <th>Типы оплат</th>
          <th class="col_price">Общая сумма</th>
        </tr>
        {$delivery_block}
      </table>
    </div>
    <div class="fl_r rb">
      <div class="center m-b bold">Типы оплат</div>
      <table style="">
        <tr>
          <th class="col_name">Тип</th>
          <th class="col_price">Сумма</th>
        </tr>
        {$pay_types}
        <tr>
          <td>Общая сумма:</td>
          <td><div class="brd_btm">{$order_sum_on_day}</div></td>
        </tr>
      </table>
      <div class="">
    <div class="bold" style="
      font-size: 16px;
      margin-top: 40px;
  ">
          Заказов: {$format_count} <br><br>
          Итого: {$order_sum_on_day}
          </div>
                <div class="" style="
     width: 100%;
margin-top: 20px;
font-weight: bold;
font-size: 14px;
  ">  
    Дата: {$date} <br><br> Подпись: <span style="
      
      border-bottom: 1px solid;
      width: 75%;
      display: inline-block;
      margin-left: 0%;
  
  "></span>
                    
                 </div>
          </div>
    </div>
    <div class="auto">
      Автоматизировано itlab.kz
    </div>
  </div>
</div>
HTML;
  }
}
