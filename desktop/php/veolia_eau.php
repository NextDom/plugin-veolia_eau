<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'veolia_eau');
$eqLogics = eqLogic::byType('veolia_eau');
?>

<div class="row row-overflow">
    <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add">
                    <i class="fa fa-plus-circle"></i> {{Ajouter un compte Veolia}}
                </a>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
            </ul>
            <ul id="ul_eqLogicView" class="nav nav-pills nav-stacked"></ul> <!-- la sidebar -->
        </div>
    </div>

    <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
        <legend>{{Mes comptes Veolia}}</legend> <!-- changer pour votre type d'équipement -->

        <div class="eqLogicThumbnailContainer"></div> <!-- le container -->
    </div>

    <!-- Affichage de l'eqLogic sélectionné -->
    <div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
        <form class="form-horizontal">
            <fieldset>
                <legend>
                    <!-- Retour au Général et affichage de la configuration avancée -->
                    <i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i> {{Général}}
                    <i class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible' data-action='configure'></i>
                </legend>
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
                            //echo '<option value="3">Service Eau du Grand Lyon</option>';
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
                    <label class="col-sm-3 control-label">{{Index de départ}}</label>
                    <div class="col-sm-3">
                        <input type="number" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="depart" placeholder="0123456789"/>
                    </div>
                </div>

            </fieldset>
        </form>


        <form class="form-horizontal">
            <fieldset>
                <legend>
                    <i class="fa fa-bullhorn"></i> {{Alertes}}
                </legend>
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

		<legend>{{Commande}}</legend>
		<table id="table_cmd" class="table table-bordered table-condensed">
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

        <form class="form-horizontal">
            <fieldset>
                <div class="form-actions">
                    <a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
                    <a class="btn btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
                </div>
            </fieldset>
        </form>

    </div>
</div>

<?php include_file('desktop', 'veolia_eau', 'js', 'veolia_eau'); ?>
<?php include_file('core', 'plugin.ajax', 'js'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
