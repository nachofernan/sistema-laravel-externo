@if (!app()->isLocal())
<img src="https://{{$_SERVER['SERVER_NAME']}}/registroproveedores/img/logo.png" alt="">
@else
<img src="http://{{$_SERVER['SERVER_NAME']}}/portalproveedores/public/img/logo.png" alt="">
@endif
