const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function sandbox(file, ready = false) {
  const intervals = [];
  const location = {href:'http://localhost/game.php?page=shipyard&mode=defense'};
  const stub = {text(){return this;},remove(){return this;},html(){return this;},
    data(){return 0;},each(callback){callback.call({});return this;},
    ready(callback){if(ready)callback();}};
  const context = {
    document:{location,title:'',getElementById(){return {options:[]};}},
    window:{location,clearInterval(){},setTimeout(callback){if(typeof callback==='function')callback();},
      setInterval(callback){intervals.push(callback);}},
    $: value => {if(typeof value==='function'){if(ready)value();return stub;}return stub;},
    URL, Date, Math, Option:function(){}, Ready:'Terminé', Gamename:'Risistar',
    serverTime:new Date(100000),startTime:100000,GetRestTimeFormat:()=>'',interval:0
  };
  vm.createContext(context);
  vm.runInContext(fs.readFileSync('scripts/game/'+file,'utf8'),context);
  return {context,intervals,location};
}
for (const file of ['buildlist.js','research.js']) {
  const {context,location}=sandbox(file);
  context.Buildlist();
  assert.equal(new URL(location.href,'http://localhost').searchParams.get('passive_reload'),'queue',file);
}
{
  const {intervals,location}=sandbox('overview.js',true);
  intervals[1]();
  assert.equal(new URL(location.href,'http://localhost').searchParams.get('passive_reload'),'queue');
}
{
  const {context,location}=sandbox('shipyard.js');
  context.Shipyard=[['Cargo',1,0,'cargo']];
  context.Amount={sub(){},toString(){return '0';}};
  context.hanger_id=0;context.ShipyardInterval=0;
  context.BuildlistShipyard();
  const url=new URL(location.href);
  assert.equal(url.searchParams.get('passive_reload'),'queue');
  assert.equal(url.searchParams.get('mode'),'defense');
}
for(const file of ['fleetTable.js']) {
  const {location,intervals}=sandbox(file,file==='fleetTable.js');
  const before=location.href;
  for(const callback of intervals)callback();
  assert.equal(location.href,before,file+' countdown sends no reload');
}
console.log('Actual building, research, overview and shipyard reload scripts mark passive requests; fleet countdown stays local.');
