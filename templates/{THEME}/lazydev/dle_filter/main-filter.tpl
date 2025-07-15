<div class="filter-block" id="filter-wrap">
	<form data-dlefilter="dle-filter" class="filter-block__form">
		<div class="filter-block__cell">
		<div class="fb-sect-name">Choisissez une catégorie</div>
			<div class="filter-block__cell-content filter-block__cell-content-columns">
                <select name="cat" data-placeholder="Toute catégorie">
					<option></option>
					<option value="1">Cms</option>
					<option value="2">Plugin/modules</option>
					<option value="3">Thèmes DLE</option>
					<option value="4">Astuces</option>
					<option value="5">Correctifs Dle-Forum</option>
					<option value="6">Upgrade</option>
					<option value="7">Documentation</option>
					<option value="8">Autres scripts</option>
					<option value="34">Dle-Forum</option>
					<option value="35">Hack Dle-Forum</option>
					<option value="36">Thèmes Dle-Forum</option>
					<option value="38">BulletShare Board</option>
					<option value="39">Thèmes BulletShare</option>
					<option value="40">Hacks BulletShare</option>
					<option value="42">Théme BulletShare v3</option>
					<option value="43">PacaPrez</option>
					<option value="44">Design PacaPrez</option>
					<option value="45">Script PacaPrez</option>
					<option value="46">Thème Dle_Forum V3.0</option>
				</select>                           
			</div>
		</div>      
     
		<div class="filter-block__cell">
		<div class="fb-sect-name">Rechercher par sorte</div>
			<div class="filter-block__cell-content filter-block__cell-content-columns">
				<select name="sort" data-placeholder="Selectionner">
					<option></option>
					<option value="date">Par date d'ajout de l'article</option>
					<option value="editdate">Par date d'édition de l'article</option>
					<option value="title">Par ordre alphabétique</option>
					<option value="comm_num">Par nombre de commentaires</option>
					<option value="news_read">Par nombre de vues</option>
					<option value="rating">Par note</option>
					<option value="author">Par auteur</option>                  
				</select> 
			</div>
		</div>     
     
		<div class="filter-block__cell">
		<div class="fb-sect-name">Trier par</div>
			<div class="filter-block__cell-content filter-block__cell-content-columns">
				<select name="sort" data-placeholder="Selectionner le tri">
					<option value="">Sélectionnez</option>
 					<option value="desc">Ordre décroissant</option>
 					<option value="asc">Ordre croissant</option>
				</select>
			</div>
		</div>         
     <div class="filter-ctrl__column">
		<div class="filter-ctrl__cell">
            <div class="cena-title-slider" style="text-align: center;">Цена:</div>
            <input name="r.prate" data-slider-config="Double slider;Сетка;Minimum value:1;Maximum value:5" data-slider-lang="fr-FR" value="" type="text">
        </div>      
	 </div>

		<div class="filter-block__cell filter-block__cell--padding" style="margin-top:30px;">
			<div class="filter-block__cell-content filter-block__cell-content--two-columns">
					<input type="button" class="btn btn-outline-secondary" data-dlefilter="submit" value="Rechercher">
					<input type="button" class="btn btn-outline-danger" data-dlefilter="reset" value="Annuler">
			</div>
		</div>
	</form>
</div>