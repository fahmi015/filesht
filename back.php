<?php

namespace App\Helpers;
use App\Models\Service;
use App\Models\Group;
use App\Models\Currency;
use App\Models\Booking;

class Helpers
{
    public function checkifpaied($row){
        if($row=='دفع على الإنترنت'){
            return 1;
        }
        return 0;
    }
    public function checkifconfirmed($row){
        if($row=='ok'){
            return 'confirmado';
        }else if($row=='cancelled_by_guest'){
            return 'cancelado';
        }
        return 'pendiente';
    }

    public function findCurrency($row){
        if(strpos($row, 'EUR') !== false){
            $currency=Currency::where('code','EUR')->get();
            return $currency[0]->id;
        }else if(strpos($row, 'MAD') !== false){
            $currency=Currency::where('code','MAD')->get();
            return $currency[0]->id;
        }else if(strpos($row, 'USA') !== false){
            $currency=Currency::where('code','USA')->get();
            return $currency[0]->id;
        }
    }

    public function feesCalc($taxes){
        $total=0;
        foreach($taxes as $tax){
            $total=$total+$tax->tax;
        }
        return $total;

    }

    
    
    public function feesResult($tax){
        return $tax * self::getNightCount() * self::getTotalPersone();
    }
    
    public function BookingTax($tax,$nights,$persons){
        return $tax * $nights * $persons;

    }
    public function priceByDays($price,$promotion,$withOffer=true,$withCurrency=true,$tax){
        
        if($promotion > 0 && $withOffer){   
            
            return  self::withIfCurrncy((($price + ($price * ($promotion / 100))) * (self::getNightCount() * self::getRoomCount()) + $tax),$withCurrency) ; 
        }
        return self::withIfCurrncy(($price  * self::getNightCount() * self::getRoomCount()) + $tax,$withCurrency);
    }


    public function priceByOne($price,$promotion,$withOffer=true,$withCurrency=true){
        if($promotion > 0 && $withOffer){   
            return  self::withIfCurrncy(($price + ($price * ($promotion / 100) ) ),$withCurrency);
        }
        return self::withIfCurrncy($price,$withCurrency);
    }
     
    public function withIfCurrncy($price,$withCurrency=true){
        if($withCurrency){
            return self::withCurrncy($price);
        }
        return $price;
    }
    public function getMaxAdults(){
        $rooms=self::getRooms();
        $max=$rooms[0]->adults;
        for($i=0;$i < count($rooms);$i++){
            if($max<$rooms[$i]->adults){
                $max=$rooms[$i]->adults;
            }
        }
        return $max;
    }
    public function findPrice($prices,$onlyprice=true){
        $max=self::getMaxAdults(self::getRooms());
        $statu=0;
        $price=null;
        foreach($prices as $price){
            if($max>=$prices[$i]->adults){
                $statu=1;
                $price=$prices[$i];
            }
        }
        if($statu==1){
            return $onlyprice ? $price->price : $price;
        }else{
            return $onlyprice ? $prices[count($prices) - 1]->price : $prices[count($prices) - 1] ;
        }
    }

    public function getTotalPersone(){
        //get persons for tax
        $rooms=self::getRooms();
        
        $total=0;
        for($i=0;$i < count($rooms);$i++){
            
            $total=$total+$rooms[$i]->adults;
            for($p=0;$p < $rooms[$i]->children;$p++){
                if($rooms[$i]->childrens[$p]->age > 6){
                    $total=$total+1;
                }  
            }
            
        }
        return $total;
    }

    public function statuColor($status){
        switch ($status) {
            case "pendiente":
                return  "bg-warning";

            case "semiconfirmado":
                
                return  "bg-info";
                
            case "confirmado":
                return  "bg-success";
            case "cancelado":
                return  "bg-danger";
            default:
                return "bg-black";    
                
        }
    }

    public function statuPayColor($status){
        switch ($status) {
            case 0:
                return  "bg-danger";
                
            case 1:
                return  "bg-success";
                
            
                
        }
    }
    public function getPriceCheckList($price,$per){
        return $price * ($per / 100);
    }














































    //////////////////////////////////////////////
    public function getRoomsSchuld($config){
        $r=0;
        foreach(self::getRooms() as $room){
            
            if(($room->adults % $config->max_adults) == 1){
                $r=$r+$room->adults / $config->max_adults;
            }else{
                $r=$r+floor($room->adults / $config->max_adults);
            }
        }
        //is_double($r) ? floor($r) + 1 : 
        return floor($r);
    }

    public function getFullTotal($total,$fee)
    {
        return $total+$fee;
    }

    public function booking_unread()
    {
        return Booking::where('is_read',0)->count();
    }

    public function calc_percentage($total,$percentage)
    {
        if($percentage == 0){
            return 0.00;
        }
        return self::number_to_price((($total * $percentage) / 100),false);
    }
    
    public function calucPer($total,$number){

        return  $total > 0 ? ($number * 100) / $total : 0;
    }
    public function total($from,$to,$price){  
        $total=0;
        $interval = date_diff(date_create($from), date_create($to));
        $interval=$interval->format('%a');    
        if($interval==0){
            $interval = 1;
        }
        $total=$total+ ($price * $interval);
        return $total;
    }
    public static function getNightBooking($start,$end)
    {
        if(($start && $end) && ($start != $end) ){
            $origin = date_create($start);
            $target = date_create($end);
            $interval = date_diff($origin, $target);
            return $interval->format('%a');
        }
        else{
            return 1;
        }
        

    } 
    
    public function totalCheckOut($configs,$start,$end){
        $total=0;
        $nightCount=self::getNightBooking($start,$end);
         foreach($configs as $config){
            $total=$total + ( $config->pivot->price * $nightCount);  
        } 
        return $total;
        
    }
    public function group($id){  
        $group=Group::find($id);
        return $group;
    }
    public function getGroup($id){  
        return count(self::group($id)->guests);
    }
    public function responsable($id){
        $group=self::group($id);
        $guests=$group->guests;
        if(count($guests)>0){
            return $guests[0]->fname  .' '.$guests[0]->lname ;
        }else{
            return '';
        }
        
    }
    public function responsable_fname($id){
        $group=self::group($id);
        $guests=$group->guests;
        if(count($guests)>0){
            return $guests[0]->fname;
        }else{
            return '';
        }
        
    }
    public function responsable_lname($id){
        $group=self::group($id);
        $guests=$group->guests;
        if(count($guests)>0){
            return $guests[0]->lname ;
        }else{
            return '';
        }
        
    }
    public function responsable_phone($id){
        $group=self::group($id);
        $guests=$group->guests;
        if(count($guests)>0){
            return $guests[0]->phone ;
        }else{
            return '';
        }
        
    }
    public function responsable_email($id){
        $group=self::group($id);
        $guests=$group->guests;
        if(count($guests)>0){
            return $guests[0]->email ;
        }else{
            return '';
        }
        
    }
    public function responsable_adr($id){
        $group=self::group($id);
        $guests=$group->guests;
        if(count($guests)>0){
            return $guests[0]->home_adr ;
        }else{
            return '';
        }
        
    }
    public function ice($id){
        $group=self::group($id);
        $guests=$group->guests;
        if(count($guests)>0){
            return $guests[0]->ICE ;
        }else{
            return '';
        }
        
    }
    public function ste($id){
        $group=self::group($id);
        $guests=$group->guests;
        if(count($guests)>0){
            return $guests[0]->ste ;
        }else{
            return '';
        }
        
    }
    

    public function payment_name($name){
        
        $name = preg_replace('~[^\pL\d]+~u',' ', $name);
        return $name;
    }

    

    public function slug($text,$divider= '-'){
        // replace non letter or digits by divider
        $text = preg_replace('~[^\pL\d]+~u', $divider, $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, $divider);

        // remove duplicate divider
        $text = preg_replace('~-+~', $divider, $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }
        return $text;
    }
    public function getGatewayFees($total,$fee){
        return $total +($total * $fee)/ 100;
    }
    public function getValueGatewayFees($total,$fee){
        return ($total * $fee)/ 100;
    }
    
    

    public function check($room){

    


        $start = date("Y-m-d", strtotime(self::getOnlyStart()));  
        $end = date("Y-m-d", strtotime(self::getOnlyEnd()));  

        $room=\App\Models\Room::where('id',$room->id)->with(['configs'=> function($query) use($start,$end){
            return $query->where('status',1)
            ->with(['stop_sales','bookings'=> function ($query){
                return $query->where('payment_statu',1)->orWhere('statu','confirmado')->select(['from','to']);
            }]);
        }])->get();
        $list=array(); 

        foreach ($room[0]->configs as $config){
            $status=true;
            foreach ($config->bookings as $item){
                if(((($start >= $item->from) && ($start < $item->to)) || (($end > $item->from) && ($end < $item->to)) )
                    )
                    {
                    $status=false;
                    break;
                }  
            }
            if(count($config->stop_sales)>0){
                foreach ($config->stop_sales as $stop_sale){
                    if(((($start >= $stop_sale->from_date) && ($start < $stop_sale->to_date)) || (($end > $stop_sale->from_date) && ($end < $stop_sale->to_date)) )
                    )
                    {
                        $status=false;
                        break;
                    }  
                } 
                
            }
            
            
            if($status){
                array_push($list,$config->id);
            }
        }
        return count($list);

    }

    static public function getType($service_id){  
        return Service::Find($service_id)->type_id;
    }
    
    public static function getNightCount()
    {
        if((session('start') && session('end')) && (session('start') != session('end')) ){
            $origin = date_create(session('start'));
            $target = date_create(session('end'));
            $interval = date_diff($origin, $target);
            return $interval->format('%a');
        }
        else{
            return 1;
        }
    } 
    
    public static function withCurrncy($price){
        $price=round($price * self::getCurrencyValue(),2);
        return $price.' '.self::getCurrency(); 
    }
    public static function withCurrncyValue($price){
        return round($price * self::getCurrencyValue(),2);
    }   
    public static function getTravels(){
        $count=0;
        $rooms=self::getRooms();
        for($i=0;$i < self::getRoomCount() ;$i++){
            $count=$count+$rooms[$i]->adults;
            $count=$count+$rooms[$i]->children;
        }
        return $count;
    }
    public static function getRooms()
    {
        return session('rooms');        
    }
    public static function getStart()
    {
        return date("D M j", strtotime(session('start')));        
    }
    public static function getEnd()
    {
        return date("D M j", strtotime(session('end')));
    }
    public static function getTirStart()
    {
        return date("M/D/j", strtotime(session('start')));        
    }
    public static function getTirEnd()
    {
        return date("M/D/j", strtotime(session('end')));
    }

    public static function getOnlyStart()
    {
        return session('start');        
    }
    public static function getOnlyEnd()
    {
        return session('end');
    }

    public static function getRoomCount()
    {
        if(session('room_count')){
            
            return session('room_count');
        }
        else{
            return 1;
        }
        
    } 

  
    public static function getTotal($room,$withOffer = true)
    {   
        $cost=self::getTotalWithoutCurrncy($room,$withOffer);
        
        return $cost.''.self::getCurrencyCode();
    }

    public static function getTotalWithoutCurrncy($room,$withOffer = true)
    {   
        return self::getPriceWithoutCurrncy($room,$withOffer) * self::getNightCount() * self::getRoomCount();
    }




    public static function getPrice($room,$withOffer = true)
    {
        $cost  = $room->price->price;
        return self::preparePrice($cost,$room,$withOffer);
    }
    


    public static function getPriceWithoutCurrncy($room,$withOffer = true)
    {
        $cost  = $room->price->price;
        $sf = self::getServicefee($cost,$room->sf);
        $markup = self::getMarkup($cost,$room->markup);
        $offer = 0;
        if($withOffer){
            $offer =self::getOffer($sf,$room->promotion);
        }
        $sfcedido = self::getSFCedido($room->sfcedido,$offer,$sf);
        return self::number_to_price($cost  + $sf + $markup - $offer - $sfcedido,false);
    }


    public static function getServicefee($price , $sf_model)
    {
        $serviceFeeMAX = $sf_model->max;
        $serviceFeeMIN = $sf_model->min;
        $serviceFee = ($sf_model->sf * $price) / 100;
        $serviceFee = $serviceFeeMAX < $serviceFee ? $serviceFeeMAX : ($serviceFeeMIN > $serviceFee ? $serviceFeeMIN : $serviceFee);
        return $serviceFee;
    }

    public static function getMarkup($cost,$markup_model)
    {
        $markupMAX = $markup_model->max;
        $markupMIN = $markup_model->min;
        $markup = ($markup_model->markup * $cost) / 100;
        $markup = $markupMAX < $markup ? $markupMAX : ($markupMIN > $markup ? $markupMIN : $markup);
        return  $markup;
    }

    public function getOffer($sf,$offer_model)
    {
        if(!isset($offer_model)){
            return 0;
        }
        //$promotionMAX = $offer_model->offer_max;
        //$promotionMIN = $offer_model->offer_min;
        $promotion = (($sf * $offer_model->percentage) / 100);
        //$promotion = $promotionMAX < $promotion ? $promotionMAX : ($promotionMIN > $promotion ? $promotionMIN : $promotion);
        return  $promotion;
    }


    public function getSFCedido($sfcedido_model,$promotion,$sf)
    {
        if(!isset($sfcedido_model)){
            return 0;
        }
        $SFCedidoMAX = $sfcedido_model->max;
        $SFCedidoMIN = $sfcedido_model->min;
        $sfcedido = (($sf - $promotion) * $sfcedido_model->sfcedido) / 100;
        $sfcedido = $SFCedidoMAX < $sfcedido ? $SFCedidoMAX : ($SFCedidoMIN > $sfcedido ? $SFCedidoMIN : $sfcedido);
        return $sfcedido;
    }


    
   

    


    

    public static function preparePrice($cost,$room,$withOffer=true,$withCurrency=true)
    {
        $sf = self::getServicefee($cost,$room->sf);
        $markup = self::getMarkup($cost,$room->markup);
        $offer = 0;
        if($withOffer){
            $offer = self::getOffer($sf,$room->promotion);
        }
        $sfcedido = self::getSFCedido($room->sfcedido,$offer,$sf);

        return self::number_to_price($cost  + $sf + $markup - $offer - $sfcedido,$withCurrency);
    }
    public static function number_to_price($number ,$withCurrency = true)
    {
        $price = floor($number * self::getCurrencyValue() );
        $fraction = $number - $price;
        if($fraction < 0.99){
            $price = $price + 1;
            $price = $price.'';
        }
        elseif($fraction == 0){
            $price = $price.'';
        }

        if($withCurrency){
            return $price . ' ' . self::getCurrencyCode();
        }
        return $price;
    }
    public static function getCurrencyValue()
    {
        if(session('currency_value') !== null){
            return session('currency_value');
        }
        return config('app.currency_value');
    }
    public static function getCurrency()
    {
        if(session('currency') !== null){
            return session('currency');
        }
        return config('app.currency');
    }
    public static function getCurrencyCode()
    {
        if(session('currency_value') !== null){
            return session('currency');
        }
        return config('app.currency');
    }
    public static function getCurrencyId()
    {
        if(session('currency_value') !== null){
            return session('currency_id');
        }
        return config('app.currency_id');
    }
    public static function getLang()
    {
        if(session('locale') !== null){
            return session('locale');
        }
        return config('app.locale');
    }
    
    public static function getImgLnag()
    {
        $images=[
            'ar'=> 'https://t-cf.bstatic.com/static/img/flags/new/48-squared/sa/44ab510f37755d1d9d4c4dfa9b1f25bed9b2a95c.png',
            'fr'=> 'https://t-cf.bstatic.com/static/img/flags/new/48-squared/fr/c48bc65c9dc57035fa983df37e9732c0f0a2663f.png',
            'en'=> 'https://t-cf.bstatic.com/static/img/flags/new/48-squared/us/fa2b2a0e643c840152ba856a8bb081c7ded40efa.png',
            'es'=> 'https://t-cf.bstatic.com/static/img/flags/new/48-squared/es/b3bd4690290a78b1303198dd6576bdab8d7f9a80.png'
        ];
        return $images[self::getLang()]; 
    }
    public static function currencys()
    {
        return Currency::all();
    }
    
    
    public static function getPercentage($room){
       
        return self::number_to_price((self::getPriceWithoutCurrncy($room,false) + ($room->sf->sf * ($room->promotion->percentage/100) ))  * self::getNightCount() * self::getRoomCount(),false);
    }
    public static function getPercentageTotalWithCurrncy($room){
        if($room->promotion->percentage > 0 && (self::getPercentage($room) > self::getTotalWithoutCurrncy($room))){
            return self::withCurrncy(self::getPercentage($room));
        }
        return  0;
    }


    

    

    

}


?>
