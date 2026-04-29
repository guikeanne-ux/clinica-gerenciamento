import { initMasks } from '../../core/js/masks.js'; initMasks();
const token=localStorage.getItem('token'); if(!token) location.href='../auth/login.html';
const api=async(u,m='GET',b=null)=>{const r=await fetch(u,{method:m,headers:{'Content-Type':'application/json',Authorization:'Bearer '+token},body:b?JSON.stringify(b):null});const j=await r.json();return{r,j}};
const list=document.getElementById('list');
async function load(){const s=document.getElementById('search').value;const {j}=await api('/api/v1/suppliers?search='+encodeURIComponent(s));list.innerHTML='';(j.data.items||[]).forEach(x=>{const c=document.createElement('article');c.className='card';c.innerHTML=`<h3>${x.name_or_legal_name}</h3><p>${x.document||'-'}</p><button class='btn btn-sm' data-e='${x.uuid}'>Editar</button>`;list.appendChild(c);});document.querySelectorAll('[data-e]').forEach(b=>b.onclick=()=>edit(b.dataset.e));}
async function edit(uuid){const {j}=await api('/api/v1/suppliers/'+uuid);const f=document.getElementById('form');Object.entries(j.data).forEach(([k,v])=>{const el=f.querySelector(`[name='${k}']`);if(el)el.value=v??''});}
document.getElementById('btn-search').onclick=load;
document.getElementById('save').onclick=async()=>{const f=document.getElementById('form');const d=Object.fromEntries(new FormData(f));const isEdit=!!d.uuid;const {r,j}=await api(isEdit?'/api/v1/suppliers/'+d.uuid:'/api/v1/suppliers',isEdit?'PUT':'POST',d);document.getElementById('feedback').innerHTML=`<div class='alert ${r.ok?'alert-success':'alert-error'}'>${j.message}</div>`;if(r.ok){f.querySelector('[name="uuid"]').value=j.data.uuid;load();}};
load();
