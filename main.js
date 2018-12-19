"use strict";

const fs = require('fs');
const less = require('less');
const path = require('path');

const convert = (source, target) => new Promise((resolve, reject) => fs.readFile(source, 'utf8', (err, data) => {
	if (err)
		return reject(err);

	less
		.render(data, {
			filename: source
		})
		.then(function (output) {
			fs.writeFile(target, output, (err) => {
				if (err)
					return reject(err);

				console.log('built ' + target);

				resolve();
			});
		});
}));

const recurse = (parent, callback) => new Promise((resolve, reject) => fs.readdir(parent, { withFileTypes: true }, (err, files) => {
	const promises = [];

	for (var file of files) {
		const source = path.join(parent, file.name);

		if (file.isFile() && file.name.endsWith('.less') && !file.name.endsWith('.inc.less'))
			promises.push(callback(source, source.replace(/\.less$/, '.css')));
		else if (file.isDirectory())
			promises.push(recurse(source, callback));
	}

	Promise.all(promises)
		.catch(reject)
		.then(resolve);
}));

const remove = (source, target) => new Promise((resolve, reject) => fs.unlink(target, (err) => {
	if (err && err.code !== 'ENOENT')
		return reject(err);

	console.log('removed ' + target);

	resolve();
}));

const action = process.argv[2];

switch (action) {
	case 'build':
	case 'start':
		recurse('src/static', convert)
			.catch(err => console.log(err))
			.then(() => console.log('you can now deploy yAronet with option "engine.text.display.use-less" set to "false".'));

		break;

	case 'clean':
		recurse('src/static', remove)
			.catch(err => console.log(err))
			.then(() => console.log('switch option "engine.text.display.use-less" set to "true" if you want to run website locally.'));

		break;

	default:
		console.error(`unknown action "${action}"`);

		break;
}
