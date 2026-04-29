import { initMasks } from '../../core/js/masks.js'; initMasks();
const token = localStorage.getItem('token'); if(!token) location.href='../auth/login.html';
const api=async(u,m='GET',b=null)=>{const r=await fetch(u,{method:m,headers:{'Content-Type':'application/json',Authorization:'Bearer '+token},body:b?JSON.stringify(b):null});const j=await r.json();return{r,j}};
const list=document.getElementById('list');
async function load(){const s=document.getElementById('search').value;const {j}=await api('/api/v1/professionals?search='+encodeURIComponent(s));list.innerHTML='';(j.data.items||[]).forEach(p=>{const c=document.createElement('article');c.className='card';c.innerHTML=`<h3>${p.full_name}</h3><p>${p.email||'-'}</p><div class='row-wrap'><button class='btn btn-sm' data-e='${p.uuid}'>Editar</button><button class='btn btn-sm' data-u='${p.uuid}'>Virar usuário</button></div>`;list.appendChild(c);});document.querySelectorAll('[data-e]').forEach(b=>b.onclick=()=>edit(b.dataset.e));document.querySelectorAll('[data-u]').forEach(b=>b.onclick=()=>createUser(b.dataset.u));}
async function edit(uuid){const {j}=await api('/api/v1/professionals/'+uuid);const f=document.getElementById('form');Object.entries(j.data).forEach(([k,v])=>{const el=f.querySelector(`[name='${k}']`);if(el)el.value=v??''});}
async function createUser(uuid){const {j}=await api('/api/v1/professionals/'+uuid+'/create-user','POST');document.getElementById('feedback').innerHTML=`<div class='alert alert-success'>${j.message}</div>`;load();}
document.getElementById('btn-search').onclick=load;
document.getElementById('save').onclick=async()=>{const f=document.getElementById('form');const d=Object.fromEntries(new FormData(f));d.also_user=!!d.also_user;const isEdit=!!d.uuid;const {r,j}=await api(isEdit?'/api/v1/professionals/'+d.uuid:'/api/v1/professionals',isEdit?'PUT':'POST',d);document.getElementById('feedback').innerHTML=`<div class='alert ${r.ok?'alert-success':'alert-error'}'>${j.message}</div>`;if(r.ok){f.querySelector('[name="uuid"]').value=j.data.uuid;load();}};
document.getElementById('create-user').onclick=()=>{const uuid=document.querySelector('[name="uuid"]').value;if(uuid)createUser(uuid);};
load();
