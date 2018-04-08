<?php

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('veolia_eau');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());

?>

<div class="row row-overflow">
    <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add">
                    <i class="fa fa-plus-circle"></i> {{Ajouter un compte Veolia}}
                </a>
                <li class="filter" style="margin-bottom: 5px;">
                    <input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/>
                </li>
                <?php
                    foreach ($eqLogics as $eqLogic) {
                        $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
                        echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '" style="' . $opacity . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
                    }
                ?>
            </ul>
        </div>
    </div>

    <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
        <div class="eqLogicThumbnailContainer">
        <div class="cursor eqLogicAction" data-action="add" style="text-align: center; background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
            <i class="fa fa-plus-circle" style="font-size : 7em;color:#94ca02;"></i>
            <br>
            <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02">{{Ajouter}}</span>
        </div>
        <div class="cursor eqLogicAction" data-action="gotoPluginConf" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
            <center>
                <i class="fa fa-wrench" style="font-size : 6em;color:#767676;"></i>
            </center>
            <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>{{Configuration}}</center></span>
        </div>
        </div>
        <legend>{{Mes comptes Veolia}}</legend> <!-- changer pour votre type d'équipement -->
        <div class="eqLogicThumbnailContainer">
            <?php
                foreach ($eqLogics as $eqLogic) {
                    $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
                    echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="text-align: center; background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
                    echo '<img src="' . $plugin->getPathImgIcon() . '" height="105" width="95" />';
                    echo "<br>";
                    echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;">' . $eqLogic->getHumanName(true, true) . '</span>';
                    echo '</div>';
                }
            ?>
        </div>
    </div>

    <!-- Affichage de l'eqLogic sélectionné -->
    <div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
        <a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
        <a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
        <a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>

        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
            <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
            <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
        </ul>

        <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
            <div role="tabpanel" class="tab-pane active" id="eqlogictab">
                <form class="form-horizontal">
                    <fieldset>
                        <div class="form-group" style="margin-top:20px;">
                            <label class="col-sm-3 control-label">{{Nom du compte Veolia}}</label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement Veolia eau}}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label" >{{Objet parent}}</label>
                            <div class="col-sm-3">
                                <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                                    <option value="">{{Aucun}}</option>
                                    <?php
                                    foreach (object::all() as $object) {
                                        echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Catégorie}}</label>
                            <div class="col-sm-8">
                            <?php
                                foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                                    echo '<label class="checkbox-inline">';
                                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                                    echo '</label>';
                                }
                            ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label" ></label>
                            <div class="col-sm-9">
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Service}}</label>
                            <div class="col-sm-3">
                                <select class="form-control configuration eqLogicAttr" id="configuration_website" data-l1key="configuration" data-l2key="website">
                                <?php
                                    echo '<option value="1">Veolia</option>';
                                    echo '<option value="2">Veolia Méditerranée</option>';
                                    echo '<option value="3">Service Eau du Grand Lyon</option>';
                                    echo '<option value="4">Tout sur mon eau / Eau en ligne</option>';
                                 ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Identifiant}}</label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="login" placeholder="Identifiant"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Mot de passe}}</label>
                            <div class="col-sm-3">
                                <input type="password" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="password" placeholder="Mot de passe"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Heure de relève}}</label>
                            <div class="col-sm-3">
                                <select class="form-control configuration eqLogicAttr" data-l1key="configuration" data-l2key="heure">
                                <?php
                                for ($heure=0; $heure<24; $heure++) {
                                    echo '<option value="'.$heure.'">'.$heure.'H00</option>';
                                }
                                ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group" id="group-configuration-depart">
                            <legend>{{...Initialisation - ne pas modifier sauf lors de l'installation ou pour re-initialiser l'historique}}</legend>
                            <label class="col-sm-3 control-label">{{Index de départ du compteur}}</label>
                            <div class="col-sm-3">
                                <input type="number" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="depart" placeholder="0123456789"/>
                            </div>
                            <div class="col-sm-3">
                                Cette valeur doit être renssigné une fois au début, c'est la valeur de votre compteur à la date ci dessous.
                             </div>
                        </div>
                        <div class="form-group" id="group-configuration-last">
                            <label class="col-sm-3 control-label">{{Date de la derniere mesure}}</label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="last" placeholder="Date de la premiere mesure a récupérer"/>
                            </div>
                            <div class="col-sm-3">
                                Modifier cette valeur pour récupérer l'historique à la veille du jour ou vous voulez reprendre.<br>
                                Il faut penser à mettre dans options avancées pour la commande <b>index</b> un lissage par le max et pas par la moyenne.
                            </div>
                        </div>
                        <div class="form-group" id="group-configuration-last">
                            <label class="col-sm-3 control-label">{{Nombre de jours de retard}}</label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="offsetVeoliaDate" placeholder="offset Veolia Date"/>
                            </div>
                            <div class="col-sm-3">
                                Nombre de jours de retard du fournisseur d'eau.
                            </div>
                        </div>
                    </fieldset>
                </form>

                <legend>{{Alertes}}</legend>
                <form class="form-horizontal">
                    <fieldset>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Maximum journalier}}</label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="maxday" placeholder="Max Journalier (litres)"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Maximum mensuel}}</label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="maxmonth" placeholder="Max mensuel (litres)"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Commande}}</label>
                            <div class="col-sm-3">
                                <div class="input-group">
                                    <input type="text"  class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="alert" />
                                    <span class="input-group-btn">
                                    <a class="btn btn-default cursor" title="Rechercher une commande" id="bt_selectAlertCmd"><i class="fa fa-list-alt"></i></a>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>

            <div role="tabpanel" class="tab-pane" id="commandtab">
                <table id="table_cmd" class="table table-bordered table-condensed" style="margin-top:20px;">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="width: 150px;">{{Nom}}</th>
                            <th style="width: 110px;">{{Sous-Type}}</th>
                            <th style="width: 150px;">{{Paramètres}}</th>
                            <th style="width: 150px;"></th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
    include_file('desktop', 'veolia_eau', 'js', 'veolia_eau');
    include_file('core', 'plugin.template', 'js');
