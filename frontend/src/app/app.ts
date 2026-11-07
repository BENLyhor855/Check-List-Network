import { Component, inject, signal } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { FormsModule } from '@angular/forms';

type RecordRow = Record<string, string | number>;
@Component({selector:'app-root',imports:[FormsModule],templateUrl:'./app.html',styleUrl:'./app.css'})
export class App {
 private http=inject(HttpClient);
 user=signal<RecordRow|null>(null); section=signal('devices'); rows=signal<RecordRow[]>([]); buildings=signal<RecordRow[]>([]); locations=signal<RecordRow[]>([]);
 error=signal(''); busy=signal(false); showForm=signal(false); offset=signal(0);
 email='';password='';name='';assetId='';manufacturer='';model='';managementIp='';category='switch';locationId=0;buildingId=0;
 constructor(){this.http.get<RecordRow>('/api/v1/auth/me').subscribe({next:u=>{this.user.set(u);this.load();},error:()=>{}});}
 login(){this.error.set('');this.busy.set(true);this.http.post('/api/v1/auth/login',{email:this.email,password:this.password}).subscribe({next:()=>{this.password='';this.http.get<RecordRow>('/api/v1/auth/me').subscribe({next:u=>{this.user.set(u);this.busy.set(false);this.load();},error:e=>this.failed(e)});},error:e=>this.failed(e)});}
 logout(){this.http.post('/api/v1/auth/logout',{}).subscribe({next:()=>{this.user.set(null);this.rows.set([]);},error:e=>this.failed(e)});}
 failed(e:HttpErrorResponse){this.busy.set(false);this.error.set(e.error?.error || 'Service unavailable. Please retry.');}
 navigate(s:string){this.section.set(s);this.offset.set(0);this.showForm.set(false);this.load();}
 load(){this.busy.set(true);this.error.set('');this.http.get<{items:RecordRow[]}>('/api/v1/'+this.section()+'?offset='+this.offset()).subscribe({next:r=>{this.rows.set(r.items);this.busy.set(false);},error:e=>this.failed(e)});}
 page(delta:number){this.offset.update(v=>Math.max(0,v+delta));this.load();}
 openForm(){this.name='';this.assetId='';this.managementIp='';this.showForm.set(true);this.http.get<{items:RecordRow[]}>('/api/v1/buildings').subscribe({next:r=>this.buildings.set(r.items),error:e=>this.failed(e)});this.http.get<{items:RecordRow[]}>('/api/v1/locations').subscribe({next:r=>this.locations.set(r.items),error:e=>this.failed(e)});}
 save(){let body:Record<string,unknown>={name:this.name};if(this.section()==='locations') body['building_id']=+this.buildingId;if(this.section()==='devices') body={...body,asset_id:this.assetId,manufacturer:this.manufacturer,model:this.model,management_ip:this.managementIp,category:this.category,location_id:+this.locationId,notes:''};this.busy.set(true);this.http.post('/api/v1/'+this.section(),body).subscribe({next:()=>{this.showForm.set(false);this.load();},error:e=>this.failed(e)});}
 keys(row:RecordRow){return Object.keys(row);}
}
