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


INSERT INTO `usuarios` (id,fk_tipo_usuario,email,pass) VALUES (1,1,'m@m.es','912ec803b2ce49e4a541068d495ab570');
