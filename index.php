<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

    <script src="source/jquery-2.2.4/jquery.min.js"></script>

    <script src="source/moment/moment-with-locales.min.js"></script>

    <script src="source/bootstrap-3.3.7/js/bootstrap.min.js"></script>

    <script src="source/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js"></script>

    <link href="source/bootstrap-3.3.7/css/bootstrap.min.css" rel="stylesheet">

    <link href="source/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css" rel="stylesheet">

    <title>Маршрут</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
</head>

<body>

<?php
require 'func.php';

session_start();

if (isset($_SESSION['State'])){
    $state = $_SESSION['State'];
} else {
    $state = new State();
}


if(isset($_POST['connect_db'])){

    $db_ip = $_POST["db_ip"];
    $db_login = $_POST["db_login"];
    $db_password = $_POST["db_password"];
    $state->setConnection($db_ip, $db_login, $db_password);

} elseif (isset($_POST['disconnect_db'])){
    $state->unsetCoordinates();
    $state->unsetConnection();

} elseif (isset($_POST['build_map'])){
    if ($state->isConnected() && ($_POST['obj_id'])){

        $object_id = $_POST['obj_id'];
        $begin_date = $_POST['begin_date'];
        $end_date = $_POST['end_date'];
        if ($_POST['zero_delete'] == "on"){
            $zero_delete = "on";
        } else {
            $zero_delete = "";
        }
        $state->setCoordinates($object_id, $begin_date, $end_date, $zero_delete);
    }
}
/*
echo $state->getZeroDelete();
echo "<br>";
echo $state->getObjectId();
echo "<br>";
echo $state->getBeginDate();
echo "<br>";
echo $state->getEndDate();
echo "<br>";
*/
?>

<div style="display:inline-block; width:55%; border:1px solid black; margin:10px; vertical-align:top;">
    <div style="display:inline-block; border:1px solid black; margin:10px; padding:10px; vertical-align:top; width:25%;">
        <?php if ($state->isConnected()): ?>
            <center><h4>Подключено к БД</h4></center><hr>
            <form method="POST" action="index.php">
                <center><h4>ip: <?=$state->getIp()?> </h4></center><hr>
                <center><button type="submit" name="disconnect_db" class="btn btn-default">Отключение</button></center>
            </form>
        <?php else: ?>
            <center><h4>Подключение к БД</h4></center><hr>
            <form method="POST" action="index.php">
                <div class="form-group"><label>ip:</label><input type="text" name="db_ip" placeholder="localhost" value="localhost" class="form-control"/></div>
                <div class="form-group"><label>login:</label><input type="text" name="db_login" placeholder="root" value="root" class="form-control"/></div>
                <div class="form-group"><label>password:</label><input type="password" name="db_password" placeholder="masterkey" value="masterkey" class="form-control"/></div>

                <center><button type="submit" name="connect_db" class="btn btn-default">Подключение</button></center>
            </form>
        <?php endif; ?>
    </div>

    <?php
    if ($state->isConnected()){

        echo '<div style="display:inline-block; border:1px solid black; margin:10px; padding:10px; vertical-align:top; width:60%;">';
        echo '<form method="POST" action="index.php">';
        echo '<div class="input-group"><label>Объект:</label><select name="obj_id" class="form-control">';
        try{
            $objects = $state->getObjectsList();
        } catch (Exception $e){
            $object = false;
        }

        if ($objects){
            foreach ($objects as $object){
                if ($state->coordinatesAreSet() && ($state->getObjectId() == $object[0])){
                    echo '<option selected value="'. $object[0] .'">('. $object[0] .') '. $object[1] .'</option>';
                } else {
                    echo '<option value="'. $object[0] .'">('. $object[0] .') '. $object[1] .'</option>';
                }

            }
        }
        echo '</select></div>';

        //2015-12-26 12:24:29

        echo '
                <div style="display:inline-block; width:47%;">
                    <label>Начало:<label>
                    <div class="input-group date" id="datetimepicker_beginDate">
                    
                        <span class="input-group-addon">
                            <span class="glyphicon glyphicon-calendar"></span>
                        </span>
                        <input required type="text" class="form-control" placeholder="Дата начала" name="begin_date" id="beginDate" value="'; if ($state->getBeginDate()){echo $state->getBeginDate();} else {echo date('Y-m-d H:i'); } echo '"/>
                    </div>
                </div>
                <div style="display:inline-block; width:47%;">
                    <label>Окончание:<label>
                    <div class="input-group date" id="datetimepicker_endDate">
                        <span class="input-group-addon">
                            <span class="glyphicon glyphicon-calendar"></span>
                        </span>
                        <input required type="text" class="form-control" placeholder="Дата окончания" name="end_date" id="endDate" value="'; if ($state->getEndDate()){echo $state->getEndDate();} else {echo date('Y-m-d H:i'); }; echo '"/>
                    </div>
                </div>
                <br>
                <div class="checkbox">
                    <label><input type="checkbox" name="zero_delete" '. $state->getZeroDelete() .'>Удалить "нули"</label>
                </div>';

                if ($state->getObjectsList()){
                echo '<div><center>
                    <button type="submit" name="build_map" class="btn btn-default">Построить маршрут</button>
                </center></div>'; } else {
                    echo '<center><h4>В базе нет объектов!</h4></center>';
                }
            echo '</form>';
        echo '</div>';


        if ($state->coordinatesAreSet()){
            $coordinates = $state->getCoordinatesArray();
            $points_count = count($coordinates);
            $json_coordinates = json_encode($coordinates);
            echo '<div>Всего найдено точек: '. $points_count . '</div>';
            echo '
            <div id="map" style="width: 100%; height: 600px"></div>
            
            
            
            <script type="text/javascript">
	            var coordinates = '. $json_coordinates .';
	            var x_middle = 0;
	            var y_middle = 0;
	            
	            
	            var coord_count = '.$points_count.';
	            
	            x_middle = coordinates[0][0];
	            y_middle = coordinates[0][1];
		            
                ymaps.ready(init);
                
                var myMap;                

                function init(){ 
                    myMap = new ymaps.Map("map", {
                        center: [x_middle	,	y_middle],
                        zoom: 12
                    }); 
                    var polyline = new ymaps.Polyline(coordinates, {
                        hintContent: "Ломаная линия"
                    }, {
                        draggable: false,
                        strokeColor: "#ff0000",
                        strokeWidth: 2,
                    });
                    myMap.geoObjects.add(polyline);
                }
            </script>';
        }
    }
    ?>
</div>

<?php

if ($state->coordinatesAreSet()){

    $data = $state->getData();
    echo '            
        <div  style="display:inline-block; border: 1px solid black; margin:10px; margin-left: 0; vertical-align:top; overflow-y: scroll; height:865px; ">
            <table class="table table-hover table-bordered">
                <tr>
                    <th style="padding:10px; text-align: center; vertical-align: middle;">#</th>
                    <th style="padding:10px; text-align: center; vertical-align: middle;">Lat</th>
                    <th style="padding:10px; text-align: center; vertical-align: middle;">Lon</th>
                    <th style="padding:10px; text-align: center; vertical-align: middle;">Date</th>
                    <th style="padding:10px; text-align: center; vertical-align: middle;">RID</th>
                </tr>';
    foreach ($data as $i => $c){
        echo '
                <tr>
                    <td style="padding:10px; text-align: center; vertical-align: middle;">'.$i.'</td>
                    <td style="padding:10px; vertical-align: middle;">'.$c[0].'</td>
                    <td style="padding:10px; vertical-align: middle;">'.$c[1].'</td>
                    <td style="padding:10px; vertical-align: middle;">'.$c[2].'</td>
                    <td style="padding:10px; vertical-align: middle;">'.$c[3].'</td>
                </tr>
                ';
    }
    echo '
                    </table>
                </div>
            ';
}

?>


<?php $_SESSION['State'] = $state; ?>

<script type="text/javascript">
    $(function () {
        $('#datetimepicker_beginDate').datetimepicker(
            {pickTime: true, language: 'ru', format: 'YYYY-MM-DD HH:mm'}
        );
    });

    $(function () {
        $('#datetimepicker_endDate').datetimepicker(
            {pickTime: true, language: 'ru', format: 'YYYY-MM-DD HH:mm'}
        );
    });
</script>


</body>
</html>
