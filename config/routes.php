<?php
/**
 * Defining a route is relatively straight forward.  The format of a route
 * definition is as follows:
 *
 * '<route name>' => array(
 *		'regex' => '<route regular expression>',
 *		'controller' => '<string>',
 *		'action' => '<string>',
 *		'parseParams' => <bool>,
 *		'extraParams' => <array>
 * )
 *
 * All attributes with the exception of 'regex' are optional.  When defining
 * the regular expression for your route, keep in mind that routes are matched
 * case insensitive, and that a wild card will automatically be added to the
 * end of whatever expression you define for matching the request parameters.
 *
 * Below is a quick definition of what each attribute does:
 *
 * regex 		Regular expression used to match the route.  To match specific
 *				pieces of the uri and assign them to given parameters, named
 *				subpatterns should be used in your regular expression.
 *				For example, the regular expression /user/(?<id>[0-9]+)
 *				would match any url of /user/ followed by any number, and
 *				the value of the number will be assigned into the request
 *				params with key id
 *
 * controller	The controller that will be used to handle the request if
 *				the route is matched			
 *
 * action		The action within the controller that will be called
 *
 * parseParams	Whether or not to split and parse the parameters that
 *				are found at the end of the request uri
 * 
 * extraParams	Predefined parameters that will be added to the request
 */
$config->routes = array(
	'default_route_with_action' => array(
		'regex' => '/(?<controller>[a-z\-_]+)/(?<action>[a-z\-_]+)'
	),
	'default_route_no_action' => array(
		'regex' => '/(?<controller>[a-z\-_]+)'
	)
);
