module.exports = {
	plugins: [
		'@babel/plugin-syntax-dynamic-import',
		'@babel/plugin-transform-runtime'
	],
	presets: [
		[
			'@babel/preset-env',
			{
				modules: 'auto'
			}
		]
	]
}
