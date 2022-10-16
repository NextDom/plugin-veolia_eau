<?php

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('veolia_eau');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());

?>

<div class="row row-overflow">
    <div class="col-xs-12 eqLogicThumbnailDisplay">
        <legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
        <div class="eqLogicThumbnailContainer">
            <div class="cursor eqLogicAction logoPrimary" data-action="add">
                <i class="fas fa-plus-circle"></i>
                <br>
                <span>{{Ajouter}}</span>
            </div>
            <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
                <i class="fas fa-wrench"></i>
                <br>
                <span>{{Configuration}}</span>
            </div>
        </div>

        <legend><i class="fas fa-table"></i> {{Mes comptes Veolia}}</legend>

        <!-- Champ de recherche -->
		<div class="input-group">
			<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic"/>
			<div class="input-group-btn">
				<a id="bt_resetSearch" class="btn roundedRight" style="width:30px"><i class="fas fa-times"></i></a>
			</div>
        </div>
        
        <div class="eqLogicThumbnailContainer">
            <?php
                foreach ($eqLogics as $eqLogic) {
                    $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
                    echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
                    echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
                    echo '<br>';
                    echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
                    echo '</div>';
                }
            ?>
        </div>
    </div>



    <div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>
                <a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a>
                <a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
                <a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>

        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
            <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
            <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
        </ul>

        <div class="tab-content">
            <div role="tabpanel" class="tab-pane active" id="eqlogictab">
                <br/>
                <form class="form-horizontal">
                    <fieldset>

                        <div class="form-group">
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
                                        foreach (jeeObject::all() as $object) {
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
                                    <option value="1">Veolia</option>
                                    <option value="2">Veolia Méditerranée</option>
                                    <option value="3">Service Eau du Grand Lyon</option>
                                    <option value="4">Tout sur mon eau / Eau en ligne</option>
                                    <option value="6">Société des eaux de l'Essonne</option>
									<option value="7">VEND'Ô - Tout sur mon eau</option>
									<option value="8">Eau de Sénart</option>
									<option value="9">Stéphanoise des Eaux</option>
									<option value="10">Seynoise des Eaux</option>
									<option value="11">Orléanaise des Eaux</option>
									<option value="12">Société des Eaux de l'Ouest Parisien (SEOP)</option>
                                    <option value="13">L'eau du Dunkerquois</option>
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
                            <label class="col-sm-3 control-label">{{Identifiant compteur}}</label>
                            <div class="col-sm-3">
                                <input type="number" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="idAbt" placeholder="0, 1, 2... si plusieurs compteurs rattaché au contrat"/>
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

                    <legend>{{Alertes}}</legend>
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
                <br/>
                <table id="table_cmd" class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{Nom}}</th>
                            <th>{{Sous-Type}}</th>
                            <th>{{Paramètres}}</th>
                            <th></th>
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
