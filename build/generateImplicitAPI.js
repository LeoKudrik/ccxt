import ccxt from '../js/ccxt.js';
import { promisify } from 'util';
import fs from 'fs';
import log from 'ololog'

const PATH = './ts/src/abstract/';
const IDEN = '    ';

const promisedWriteFile = promisify(fs.writeFile);

function isHttpMethod(method){
    return ['get', 'post', 'put', 'delete', 'patch'].includes(method);
}
//-------------------------------------------------------------------------

const capitalize = (s) => {
    return s.length ? (s.charAt (0).toUpperCase () + s.slice (1)) : s;
};

//-------------------------------------------------------------------------

function lowercaseFirstLetter(string) {
    return string.charAt(0).toLowerCase() + string.slice(1);
}

//-------------------------------------------------------------------------

function generateImplicitMethodNames(id, api, paths = []){
    const keys = Object.keys(api);
    for (const key of keys){
        let value = api[key];
        let endpoints = []
        if (isHttpMethod(key)){
            if (value && !Array.isArray(value)) {
                endpoints = Object.keys(value)
            } else {
                if  (Array.isArray(value)) {
                    endpoints = [];
                    for (const item of value){
                        if (Array.isArray(item)) {
                            endpoints.push(item[0])
                        } else {
                            endpoints.push(item)
                        }
                    }
                }
            }
            for (const endpoint of endpoints){
                const pattern = /[^a-zA-Z0-9]/g;
                const result = paths.concat (key).concat (endpoint.split(pattern));
                let completePath = result.filter(r => r.length > 0).map(capitalize).join('');
                completePath = lowercaseFirstLetter(completePath);
                storedResult[id].push(completePath)
            }
        } else {
            generateImplicitMethodNames(id, value, paths.concat([ key ]))
        }
    }
}

//-------------------------------------------------------------------------

function createImplicitMethods(){
    const exchanges = Object.keys(storedResult);
    for (const index in exchanges) {
        const exchange = exchanges[index];
        const methodNames = storedResult[exchange];

        const methods =  methodNames.map(method=> {
            return `${IDEN}abstract ${method} (params?: {}): Promise<implicitReturnType>;`
        });
        methods.push ('}')
        storedMethods[exchange] = storedMethods[exchange].concat (methods)
    }
}

//-------------------------------------------------------------------------

async function editTypesFiles(){
    const exchanges = Object.keys(storedResult);
    const files = exchanges.map(ex => PATH + ex + '.ts');
    await Promise.all(files.map((path, idx) => promisedWriteFile(path, storedMethods[exchanges[idx]].join ('\n'))))
}

//-------------------------------------------------------------------------

async function main() {
    log.bright.cyan ('Exporting TypeScript implicit api methods')
    const exchanges = ccxt.exchanges;
    for (const index in exchanges) {
        const exchange = exchanges[index];
        const exchangeClass = ccxt[exchange]
        const instance = new exchangeClass();
        let api = instance.api
        if (exchange in ccxt.pro) {
            const proInstance = new ccxt.pro[exchange] ()
            api = proInstance.api
        }
        const parent = Object.getPrototypeOf (Object.getPrototypeOf(instance)).constructor.name
        const importType = 'import { implicitReturnType } from \'../base/types.js\''
        const importParent = (parent === 'Exchange') ?
            `import { Exchange as _Exchange } from '../base/Exchange.js'` :
            `import _${parent} from '../${parent}.js'`
        const header = `export default abstract class ${parent} extends _${parent} {` // hotswap later
        storedResult[exchange] = []
        storedMethods[exchange] = [ importType, importParent, '', header ];
        generateImplicitMethodNames(exchange, api)
    }
    createImplicitMethods()
    await editTypesFiles();
    log.bright.cyan ('TypeScript implicit api methods completed!')
}

let storedResult = {};
let storedMethods = {};
main()
