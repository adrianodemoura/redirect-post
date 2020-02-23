var delete_cookie = function(name)
{
	let path 	= '/';
	let arrPath = window.location.pathname.split('/')

	path = '/'+arrPath[1] + '/' + arrPath[2] + '/'

    document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path='+path+';'

}

window.onload = function()
{
	delete_cookie( chave )
}