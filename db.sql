/* Script para la base de datos */

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fk_idioma` varchar(4) NOT NULL DEFAULT 'es',
  `fk_tipo_usuario` int(11) NOT NULL DEFAULT '0',
  `email` varchar(255) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `visitas` int(9) NOT NULL DEFAULT '0',
  `activo` int(1) NOT NULL DEFAULT '0',
  `borrado` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;


/* Primer administrador: cambia el email y sustituye HASH_DE_TU_CONTRASENA por la salida de
php -r 'echo password_hash("TU_CONTRASENA", PASSWORD_DEFAULT), "\n";'
Un MD5('...') también entra y se convierte a password_hash en el primer login, pero no se recomienda.
INSERT INTO `usuarios` (id,fk_tipo_usuario,email,pass,activo) VALUES (1,1,'admin@example.com','HASH_DE_TU_CONTRASENA',1);
*/
