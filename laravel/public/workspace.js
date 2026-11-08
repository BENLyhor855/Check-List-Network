'use strict';
const $ = id => document.getElementById(id);
let section = 'checks', offset = 0, user = null, count = 0;
const titles = {checks:'Daily checklist', devices:'Network inventory', buildings:'Buildings', locations:'Locations', audit:'Audit trail'};
const columns = {devices:['id','asset_id','name','manufacturer','model','management_ip','category','status','location','building'],buildings:['id','name'],locations:['id','building_id','name'],audit:['id','user_id','action','target','result','created_at']};
async function api(path, options={}) {
  const response = await fetch('/api/v1/'+path, {credentials:'same-origin', ...options, headers:{...(options.body ? {'Content-Type':'application/json'} : {}), ...options.headers}});
  if (!response.ok) { let body={}; try {body=await response.json()} catch {} throw new Error(body.error || `Request failed (${response.status})`); }
  return response.status === 204 ? null : response.json();
}
function showLogin() { user=null; $('workspace').hidden=true; $('login').hidden=false; $('login-form').reset(); }
async function start() { try { user=await api('auth/me'); $('login').hidden=true; $('workspace').hidden=false; $('identity').textContent=`${user.email} · ${user.role} · All record creation is audited`; await load(); } catch {showLogin()} }
async function load() {
 $('error').textContent=''; $('record-form').hidden=true;
 $('check-panel').hidden=section!=='checks'; $('inventory-panel').hidden=section==='checks'; $('inventory-notice').hidden=section==='checks'; $('title').textContent=titles[section]; $('crumb').textContent='NETWORK OPERATIONS / '+section.toUpperCase();
 document.querySelectorAll('nav button').forEach(b=>b.classList.toggle('active', b.dataset.section===section)); $('add').hidden=section==='audit'||section==='checks'; $('add').textContent='+ Add '+({devices:'device',buildings:'building',locations:'location'}[section]||'');
 if(section==='checks'){ await loadChecks(); return; }
 try {const data=await api(`${section}?offset=${offset}`); count=data.items.length; $('table-head').replaceChildren(); $('table-body').replaceChildren(); const tr=document.createElement('tr'); for(const col of columns[section]) {const th=document.createElement('th');th.textContent=col.replaceAll('_',' ');tr.append(th)} $('table-head').append(tr);
 for(const row of data.items) {const tr=document.createElement('tr');for(const col of columns[section]) {const td=document.createElement('td');td.textContent=row[col] ?? '';tr.append(td)}$('table-body').append(tr)} $('empty').textContent=count ? '' : 'No records found.'; $('page').textContent=`Showing ${count ? offset+1 : 0}–${offset+count}`; $('previous').disabled=offset===0; $('next').disabled=count<100;
 } catch(e) {$('error').textContent=e.message; $('table-body').replaceChildren();}
}
function input(name,label,type='text',required=false,max=120) {const wrapper=document.createElement('label');wrapper.textContent=label;const el=document.createElement('input');el.name=name;el.type=type;el.required=required;el.maxLength=max;wrapper.append(el);return wrapper}
function select(name,label,items) {const wrapper=document.createElement('label');wrapper.textContent=label;const el=document.createElement('select');el.name=name;el.required=true;const empty=document.createElement('option');empty.value='';empty.textContent='Select '+label.toLowerCase();el.append(empty);for(const item of items){const opt=document.createElement('option');opt.value=item.id;opt.textContent=item.name;el.append(opt)}wrapper.append(el);return wrapper}
async function openForm(){try{const fields=$('fields');fields.replaceChildren();fields.append(input('name','Name','text',true));if(section==='locations'){const data=await api('buildings');fields.append(select('building_id','Building',data.items))}if(section==='devices'){fields.append(input('asset_id','Asset ID','text',true,80),input('manufacturer','Manufacturer','text',false,80),input('model','Model','text',false,80),input('management_ip','Management IP','text',false,45));fields.append(select('category','Category',['firewall','switch','access point','server','NAS','other'].map(x=>({id:x,name:x}))));const data=await api('locations');fields.append(select('location_id','Location',data.items))}$('form-title').textContent='Add '+section.slice(0,-1);$('record-form').hidden=false;}catch(e){$('error').textContent=e.message}}
$('login-form').addEventListener('submit', async e=>{e.preventDefault();$('login-error').textContent='';const form=new FormData(e.target);try{await api('auth/login',{method:'POST',body:JSON.stringify({email:form.get('email'),password:form.get('password')})});await start()}catch(err){$('login-error').textContent=err.message}});
$('logout').addEventListener('click',async()=>{try{await api('auth/logout',{method:'POST',body:'{}'});showLogin()}catch(e){$('error').textContent=e.message}});
document.querySelectorAll('nav button').forEach(b=>b.addEventListener('click',()=>{section=b.dataset.section;offset=0;load()}));$('refresh').onclick=load;$('previous').onclick=()=>{offset=Math.max(0,offset-100);load()};$('next').onclick=()=>{offset+=100;load()};$('add').onclick=openForm;$('cancel').onclick=()=>$('record-form').hidden=true;
$('record-form').addEventListener('submit',async e=>{e.preventDefault();const body=Object.fromEntries(new FormData(e.target));for(const key of ['building_id','location_id'])if(key in body)body[key]=Number(body[key]);try{await api(section,{method:'POST',body:JSON.stringify(body)});offset=0;await load()}catch(err){$('error').textContent=err.message}});
start();

const checkDate=$('check-date');
const localToday = new Date();
checkDate.value = [localToday.getFullYear(), String(localToday.getMonth()+1).padStart(2,'0'), String(localToday.getDate()).padStart(2,'0')].join('-');
checkDate.addEventListener('change',()=>loadChecks());
$('print-report').addEventListener('click',()=>window.print());
async function loadChecks() {
  if (!checkDate.value) return;
  const date=checkDate.value;
  try {
    const data=await api('checks?date='+encodeURIComponent(date));
    if(section!=='checks'||date!==checkDate.value)return;
    const s=data.summary;
    $('check-summary').textContent=`${s.total} devices · ${s.ok} OK · ${s.issue} issues · ${s.pending} pending · ${s.not_applicable} not applicable`;
    const list=$('check-list');list.replaceChildren();
    if(!data.items.length){const p=document.createElement('p');p.textContent='No devices yet. Add a building, location and device in Network inventory to start.';list.append(p);return;}
    for(const item of data.items){
      const card=document.createElement('form');card.className='check-card';
      const heading=document.createElement('h3');heading.textContent=`${item.name} · ${item.asset_id}`;card.append(heading);
      const meta=document.createElement('p');meta.textContent=[item.manufacturer,item.model,item.category,item.building,item.location].filter(Boolean).join(' · ');card.append(meta);
      const status=select('result','Result',[{id:'ok',name:'OK'},{id:'issue',name:'Issue'},{id:'not_applicable',name:'Not applicable'}]);status.querySelector('option').textContent='Pending — select result';status.querySelector('select').value=item.result||'';card.append(status);
      const note=input('notes','Notes','text',false,2000);note.querySelector('input').value=item.notes||'';card.append(note);
      const info=document.createElement('p');info.className='footnote';info.textContent=item.checked_by?`Last recorded by ${item.checked_by} · ${item.updated_at} UTC`:'Not checked yet';card.append(info);
      const button=document.createElement('button');button.textContent='Save check';card.append(button);
      card.addEventListener('submit',async e=>{e.preventDefault();button.disabled=true;try{await api('checks',{method:'POST',body:JSON.stringify({device_id:Number(item.id),date,result:status.querySelector('select').value,notes:note.querySelector('input').value})});await loadChecks()}catch(err){$('error').textContent=err.message;button.disabled=false}});
      list.append(card);
    }
  } catch(e) {$('error').textContent=e.message;}
}
