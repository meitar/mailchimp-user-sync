/**
 * User Model
 *
 * @param data
 * @constructor
 */
var User = function( data ) {
	this.id = m.prop( data.ID );
	this.username = m.prop( data.username );
	this.email = m.prop( data.email );
};

module.exports = User;