import { Component, OnInit } from '@angular/core';
import { EnumLinkApi } from 'src/app/Enums/enum-link-api';
import { PersonneModel } from 'src/app/Models/personne-model.model';
import { TypeObjetModel } from 'src/app/Models/type-objet-model.model';
import { RequesterService } from 'src/app/Services/requester.service';
import { SecurityService } from 'src/app/Services/security.service';
import { Validators, FormBuilder, FormGroup, FormControl } from '@angular/forms';
import { EnumTypeObjet } from 'src/app/Enums/enum-type-objet';

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.css']
})
export class HeaderComponent implements OnInit {

  TypeObjetList : TypeObjetModel[]=[]
  PersonneFormGroup! : FormGroup

  constructor(
    public RequesterService : RequesterService,
    public SecurityService : SecurityService,
    private FormBuilder : FormBuilder

    ) { }

  ngOnInit(): void {

    // this.RequesterService.showspinner()
    this.InitHeader()
    this.InitFormGroup()

  }

  InitFormGroup()
  {
    this.PersonneFormGroup = this.FormBuilder.group({
      Email: new FormControl('',
      [
        Validators.required,
        Validators.pattern("^[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,4}$"),
        // this.MailExistValidator
      ]),
      MotDePasse: ['', Validators.required],

    });
  }


  async InitHeader()
  {
    await this.RequesterService.AsyncGetResponse(EnumLinkApi.GetTypeObjetById+0+'/'+1)
          this.TypeObjetList=this.RequesterService.response;
          this.TypeObjetList = this.TypeObjetList.filter(x => x.DontShowInFront==0)
          this.TypeObjetList = this.TypeObjetList.filter(x => x.id!=EnumTypeObjet.Compagnie)


    // this.RequesterService.hidespinner()
  }


  async Login()
  {
    var Personne : PersonneModel = new PersonneModel()
      Personne.Email= this.PersonneFormGroup.get('Email')?.value
      Personne.MotDePasse= this.PersonneFormGroup.get('MotDePasse')?.value
      await this.SecurityService.Login(Personne)



  }

}
